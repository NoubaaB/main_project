<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChatMessage;
use App\Services\CustomService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AIAssistantController extends Controller
{
    private CustomService $ai;

    /**
     * Cache the schema string so we don't regenerate it every call.
     */
    private static ?string $cachedSchema = null;
    private static ?string $cachedComments = null;
    private static ?string $cachedBusinessContext = null;
    private static ?string $cachedRoutes = null;
    private static ?array $cachedModelMap = null;
    private ?string $app_name = null;

    /** Models excluded from AI write/delete operations */
    private const PROTECTED_MODELS = [
        'User',
        'Role',
        'PasswordReset',
        'ChatMessage',
        'ChargeAuditLog',
        'SellAuditLog',
    ];

    public function __construct()
    {
        $this->ai = new CustomService();
        $this->app_name = env('APP_NAME');
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Main endpoint: POST /api/ai-assistant
    // """""""""""""""""""""""""""""""""""""""""
    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'messages' => 'required|string',
        ]);

        $question = $data['message'];
        $messages = $data['messages'];

        // "" Step 1: Build context ""
        $schema = $this->getSchemaString();
        $tableComments = $this->getTableCommentsString();
        $businessCtx = $this->getBusinessContext();

        // "" Step 2: Classify intent (read / create / delete / confirm / cancel) ""
        $modelMapStr = $this->getModelMapString();
        $intentPrompt = $this->buildIntentPrompt($question, $messages, $schema, $modelMapStr);
        $intentJson = $this->ai->classifyIntent($intentPrompt);
        $intentData = null;
        if ($intentJson) {
            $intentData = json_decode($intentJson, true);
        }
        $intent = $intentData['intent'] ?? 'read';

        // "" Handle WRITE/DELETE intents ""
        if (in_array($intent, ['create', 'delete'])) {
            return $this->handleWriteIntent($request, $question, $messages, $intentData, $schema, $tableComments, $businessCtx);
        }

        if ($intent === 'confirm') {
            return $this->handleConfirmIntent($request, $question, $messages, $schema, $tableComments, $businessCtx);
        }

        if ($intent === 'cancel') {
            $answer = $this->buildCancelHtml();
            $this->persistMessages($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // "" READ intent: existing SQL pipeline ""
        $sqlPrompt = $this->buildSQLPrompt($question, $messages, $schema, $tableComments, $businessCtx);
        $sqlQuery = $this->ai->generateSQL($sqlPrompt);

        $result = null;
        $sqlError = null;
        if ($sqlQuery) {
            if ($this->isSafeQuery($sqlQuery)) {
                $result = DB::select($sqlQuery);
                $result = json_encode($result);
            } else {
                $sqlError = 'Query blocked: only SELECT statements are allowed.';
            }
        }

        $routes = $this->getAppRoutes();
        $answerPrompt = $this->buildAnswerPrompt($question, $messages, $schema, $tableComments, $result, $sqlError, $businessCtx, $routes);
        $answer = $this->ai->generateAnswer($answerPrompt);

        if (!$answer) {
            $answer = 'Sorry, I could not generate an answer. Please try rephrasing your question.';
        }

        $this->persistMessages($request, $question, $answer);

        return response()->json([
            'answer' => $answer,
            'result' => $result,
            'sqlQuery' => $sqlQuery,
        ], 200);
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Write/Delete Intent Handlers
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Handle create/delete intents: validate, build confirmation, return to user.
     */
    private function handleWriteIntent(Request $request, string $question, string $messages, array $intentData, string $schema, string $tableComments, string $businessCtx)
    {
        $intent = $intentData['intent'];
        $actions = $intentData['actions'] ?? [];
        $preCreate = $intentData['pre_create'] ?? [];
        $missingInfo = $intentData['missing_info'] ?? [];
        $modelMap = $this->getModelMap();
        $errors = [];
        $validatedActions = [];

        // Get user's AI CRUD permissions
        $user = $request->user();
        $allowedCrud = $user?->allowed_ai_crud ?? [];

        foreach ($actions as $action) {
            $modelName = $action['model'] ?? '';

            // Check model exists
            if (!isset($modelMap[$modelName])) {
                $errors[] = "Model '$modelName' not found.";
                continue;
            }

            // Check protected
            if (in_array($modelName, self::PROTECTED_MODELS)) {
                $errors[] = "Model '$modelName' is protected and cannot be modified by the AI.";
                continue;
            }

            // Check user permission for this model + operation
            $operation = $intent; // 'create' or 'delete'
            $modelPerms = $allowedCrud[$modelName] ?? [];
            if (!in_array($operation, $modelPerms)) {
                $errors[] = "You don't have permission to {$operation} {$modelName} records via AI.";
                continue;
            }

            if ($intent === 'create') {
                $records = $action['records'] ?? [];
                $validatedRecords = [];
                $columns = $modelMap[$modelName]['columns'];

                foreach ($records as $record) {
                    $cleaned = [];
                    foreach ($record as $field => $value) {
                        // Skip name-reference fields (like section_name)  resolved separately
                        if (str_ends_with($field, '_name') && !in_array($field, $columns)) {
                            continue;
                        }
                        if (in_array($field, $columns)) {
                            $cleaned[$field] = $value;
                        }
                    }
                    if (!empty($cleaned)) {
                        $validatedRecords[] = $cleaned;
                    }
                }
                $action['records'] = $validatedRecords;
            }

            if ($intent === 'delete') {
                // If IDs provided, verify they exist
                $ids = $action['ids'] ?? [];
                $lookup = $action['lookup'] ?? null;

                if (empty($ids) && $lookup) {
                    // Resolve by name lookup
                    $class = $modelMap[$modelName]['class'];
                    $field = $lookup['field'] ?? 'name';
                    $value = $lookup['value'] ?? '';
                    $found = $class::where($field, 'LIKE', "%$value%")->pluck('id')->toArray();
                    $action['ids'] = $found;
                    $action['description'] = ($action['description'] ?? '') . " (found " . count($found) . " records matching '$value')";
                }

                if (empty($action['ids'])) {
                    $errors[] = "No records found to delete for $modelName.";
                    continue;
                }

                $action['soft_delete'] = $modelMap[$modelName]['soft_delete'];
            }

            $validatedActions[] = $action;
        }

        // Validate pre_create models
        $validatedPreCreate = [];
        foreach ($preCreate as $pc) {
            $pcModel = $pc['model'] ?? '';
            if (!isset($modelMap[$pcModel]) || in_array($pcModel, self::PROTECTED_MODELS)) {
                continue;
            }
            // Check user permission for pre_create (always 'create')
            $pcPerms = $allowedCrud[$pcModel] ?? [];
            if (!in_array('create', $pcPerms)) {
                $errors[] = "You don't have permission to create {$pcModel} records via AI.";
                continue;
            }
            $validatedPreCreate[] = $pc;
        }

        if (!empty($errors) && empty($validatedActions)) {
            $answer = '<div style="padding:8px;color:#D32F2F;"><strong>š ï¸ Cannot execute:</strong><ul>' .
                implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>';
            $this->persistMessages($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // Build confirmation HTML
        $pendingAction = [
            'intent' => $intent,
            'actions' => $validatedActions,
            'pre_create' => $validatedPreCreate,
            'errors' => $errors,
        ];

        $confirmHtml = $this->buildConfirmationHtml($pendingAction);

        // Store pending action as a special hidden tag in the answer
        $pendingJson = htmlspecialchars(json_encode($pendingAction), ENT_QUOTES, 'UTF-8');
        $answer = $confirmHtml . '<div class="ai-pending-action" style="display:none" data-action=\'' . $pendingJson . '\'></div>';

        $this->persistMessages($request, $question, $answer);
        return response()->json(['answer' => $answer, 'pending_action' => $pendingAction], 200);
    }

    /**
     * Handle user confirmation  find the pending action and execute it.
     */
    private function handleConfirmIntent(Request $request, string $question, string $messages, string $schema, string $tableComments, string $businessCtx)
    {
        // Find pending action in conversation history
        $pendingAction = $this->extractPendingAction($messages);

        if (!$pendingAction) {
            $answer = "I don't have a pending action to confirm. What would you like me to do?";
            $this->persistMessages($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // Execute the action plan
        $result = $this->executeActionPlan($pendingAction);
        $answer = $result['html'];

        $this->persistMessages($request, $question, $answer);
        return response()->json(['answer' => $answer, 'affected_models' => $result['affected_models']], 200);
    }

    /**
     * Public endpoint to execute an action plan directly (from frontend).
     */
    public function executeAction(Request $request)
    {
        $data = $request->validate([
            'action_plan' => 'required|array',
            'message' => 'nullable|string',
        ]);

        $actionPlan = $data['action_plan'];
        $user = $request->user();
        $allowedCrud = $user?->allowed_ai_crud ?? [];
        $intent = $actionPlan['intent'] ?? 'create';

        // Verify permissions before execution
        foreach ($actionPlan['actions'] ?? [] as $action) {
            $modelName = $action['model'] ?? '';
            $modelPerms = $allowedCrud[$modelName] ?? [];
            if (!in_array($intent, $modelPerms)) {
                $error = "You don't have permission to {$intent} {$modelName} records via AI.";
                return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;"><strong>š ï¸ ' . $error . '</strong></div>'], 403);
            }
        }
        foreach ($actionPlan['pre_create'] ?? [] as $pc) {
            $pcModel = $pc['model'] ?? '';
            $pcPerms = $allowedCrud[$pcModel] ?? [];
            if (!in_array('create', $pcPerms)) {
                $error = "You don't have permission to create {$pcModel} records via AI.";
                return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;"><strong>š ï¸ ' . $error . '</strong></div>'], 403);
            }
        }

        $result = $this->executeActionPlan($actionPlan);

        if ($user) {
            ChatMessage::withoutEvents(function () use ($user, $data, $result) {
                ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $data['message'] ?? 'Confirmed action']);
                ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $result['html']]);
            });
        }

        return response()->json(['answer' => $result['html'], 'affected_models' => $result['affected_models']], 200);
    }

    /**
     * Handle AI assistant request with Excel data.
     * The frontend parses the Excel file using SheetJS and sends headers + rows as JSON.
     */
    public function askWithExcel(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'excel_data' => 'required|array',
            'excel_data.headers' => 'required|array',
            'excel_data.rows' => 'required|array',
            'excel_data.filename' => 'nullable|string',
            'excel_data.sheet_name' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $excelData = $request->input('excel_data');
        $headers = $excelData['headers'] ?? [];
        $rows = $excelData['rows'] ?? [];
        $filename = $excelData['filename'] ?? 'uploaded_file.xlsx';
        $sheetName = $excelData['sheet_name'] ?? 'Sheet1';
        $totalRows = count($rows);

        // Build a text representation of the Excel data (limit preview for AI context)
        $previewRows = array_slice($rows, 0, 200);
        $excelText = "File: {$filename} | Sheet: {$sheetName} | Total Rows: {$totalRows}\n";
        $excelText .= "Headers: " . implode(' | ', $headers) . "\n\n";
        foreach ($previewRows as $i => $row) {
            $values = array_map(function ($v) {
                return is_null($v) ? '' : (string) $v;
            }, $row);
            $excelText .= "Row " . ($i + 1) . ": " . implode(' | ', $values) . "\n";
        }
        if ($totalRows > 200) {
            $excelText .= "\n... and " . ($totalRows - 200) . " more rows (not shown)\n";
        }

        // Detect intent: does the user want to INSERT, SUMMARIZE, or SEARCH?
        $schema = $this->getSchemaString();
        $modelMapStr = $this->getModelMapString();

        $prompt = $this->buildExcelPrompt($message, $excelText, $schema, $modelMapStr, $headers, $totalRows);

        $this->ai = new CustomService();
        $aiResponse = $this->ai->classifyIntent($prompt);

        if (!$aiResponse) {
            return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;">Sorry  could not analyze the Excel data.</div>'], 200);
        }

        $parsed = json_decode($aiResponse, true);
        if (!is_array($parsed)) {
            return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;">Sorry  invalid AI response.</div>'], 200);
        }

        $intent = $parsed['intent'] ?? 'analyze';

        // Handle ANALYZE / SUMMARIZE / SEARCH intents  use the answer generator
        if (in_array($intent, ['read', 'analyze', 'summarize', 'search'])) {
            $analysisPrompt = <<<PROMPT
            === EXCEL DATA ===
            {$excelText}

            === USER REQUEST ===
            {$message}

            === TASK ===
            Analyze the Excel data above based on the user's request. 
            - If the user wants a summary, provide a clear summary with key statistics.
            - If the user wants to search, find matching rows and present them clearly.
            - Format your response as clean HTML with inline styles.
            - Use styled tables if showing data.
            - Detect the user's language and answer in the same language.
            - Format numbers with spaces as thousands separator:
              * COUNTS (quantities, number of records, IDs): INTEGERS with NO decimals (e.g., 1 250 items).
              * PRICES (amounts in MAD): 2 decimals (e.g., 12 500.00 MAD).
              * WEIGHTS (KG): 2 decimals (e.g., 1 500.00 KG).
              * PERCENTAGES: 1 decimal (e.g., 85.3%).
            PROMPT;

            $answer = $this->ai->generateAnswer($analysisPrompt);
            if (!$answer) {
                $answer = '<div style="padding:8px;color:#D32F2F;">Sorry, could not analyze the Excel data.</div>';
            }

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $answer) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}"]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
                });
            }

            return response()->json(['answer' => $answer], 200);
        }

        // Handle INSERT intent  propose an action plan to insert Excel rows into DB
        if ($intent === 'create') {
            $actions = $parsed['actions'] ?? [];
            if (empty($actions)) {
                return response()->json(['answer' => '<div style="padding:8px;color:#FF9800;">Could not determine which model to insert into.</div>'], 200);
            }

            // Map Excel rows to model records based on AI's field mapping
            $mappedActions = [];
            foreach ($actions as $action) {
                $modelName = $action['model'] ?? '';
                $fieldMapping = $action['field_mapping'] ?? []; // AI provides {excel_col: db_field}
                $records = [];

                if (!empty($fieldMapping)) {
                    foreach ($rows as $row) {
                        $rec = [];
                        foreach ($fieldMapping as $excelCol => $dbField) {
                            $colIdx = array_search($excelCol, $headers);
                            if ($colIdx !== false && isset($row[$colIdx])) {
                                $rec[$dbField] = $row[$colIdx];
                            }
                        }
                        if (!empty($rec)) {
                            $records[] = $rec;
                        }
                    }
                } elseif (!empty($action['records'])) {
                    $records = $action['records'];
                }

                $mappedActions[] = [
                    'model' => $modelName,
                    'records' => $records,
                ];
            }

            $totalRecords = array_sum(array_map(fn($a) => count($a['records']), $mappedActions));

            // Build confirmation HTML
            $html = $this->buildConfirmationHtml([
                'intent' => 'create',
                'actions' => $mappedActions,
                'pre_create' => $parsed['pre_create'] ?? [],
            ], '📊 Excel Import');

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $html) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}"]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $html]);
                });
            }

            return response()->json([
                'answer' => $html,
                'pending_action' => [
                    'intent' => 'create',
                    'actions' => $mappedActions,
                    'pre_create' => $parsed['pre_create'] ?? [],
                ],
            ], 200);
        }

        // Fallback
        return response()->json(['answer' => '<div style="padding:8px;">I received the Excel data but couldn\'t determine what to do. Please be more specific.</div>'], 200);
    }

    /**
     * Build prompt for Excel data intent classification.
     */
    private function buildExcelPrompt(string $message, string $excelText, string $schema, string $modelMap, array $headers, int $totalRows): string
    {
        $headersList = implode(', ', $headers);
        return <<<PROMPT
        === AVAILABLE MODELS ===
        {$modelMap}

        === DATABASE SCHEMA ===
        {$schema}

        === EXCEL FILE DATA ===
        {$excelText}

        === USER MESSAGE ===
        {$message}

        === TASK ===
        The user has uploaded an Excel file with {$totalRows} rows and these columns: {$headersList}.
        
        Classify the user's intent regarding this Excel data. Return ONLY valid JSON.
        
        INTENTS:
        1. "analyze"  User wants to SUMMARIZE, UNDERSTAND, SEARCH, or GET STATISTICS from the Excel data.
        2. "create"  User wants to INSERT/IMPORT the Excel data into a database table.
        
        For "analyze": return {"intent":"analyze"}
        
        For "create": Map Excel columns to database model fields. Return:
        {"intent":"create","actions":[{"model":"ModelName","field_mapping":{"ExcelColumnName":"db_field_name"}}],"pre_create":[],"missing_info":[]}
        
        The field_mapping maps Excel column headers to the corresponding database column names.
        Match columns by semantic similarity (e.g. "Nom" → "full_name", "Prix" → "price").
        
        Return ONLY the JSON. No text before or after.
        PROMPT;
    }

    /**
     * Execute a validated action plan (create or delete records).
     */
    private function executeActionPlan(array $plan): array
    {
        $modelMap = $this->getModelMap();
        $results = [];
        $affectedModels = [];
        $intent = $plan['intent'] ?? 'create';

        try {
            DB::beginTransaction();

            // Execute pre_create first (dependencies)
            foreach ($plan['pre_create'] ?? [] as $pc) {
                $modelName = $pc['model'] ?? '';
                if (!isset($modelMap[$modelName]))
                    continue;
                $class = $modelMap[$modelName]['class'];
                $record = $class::withoutEvents(fn () => $class::create($pc['fields'] ?? []));
                $results[] = "✅ Created {$modelName} #{$record->id}";
                $affectedModels[] = $modelName;

                // Update references in main actions
                $fkField = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . '_id';
                foreach ($plan['actions'] as &$action) {
                    if (isset($action['records'])) {
                        foreach ($action['records'] as &$rec) {
                            if (isset($rec[$fkField]) && $rec[$fkField] === null) {
                                $rec[$fkField] = $record->id;
                            }
                        }
                    }
                }
            }

            // Execute main actions
            foreach ($plan['actions'] as $action) {
                $modelName = $action['model'] ?? '';
                if (!isset($modelMap[$modelName]))
                    continue;
                $class = $modelMap[$modelName]['class'];
                $affectedModels[] = $modelName;

                if ($intent === 'create') {
                    $records = $action['records'] ?? [];
                    $created = 0;
                    $class::withoutEvents(function () use ($class, $records, &$created) {
                        foreach ($records as $rec) {
                            $class::create($rec);
                            $created++;
                        }
                    });
                    $results[] = "✅ Created {$created} {$modelName} record(s)";
                }

                if ($intent === 'delete') {
                    $ids = $action['ids'] ?? [];
                    $deleted = 0;
                    $class::withoutEvents(function () use ($class, $ids, &$deleted) {
                        foreach ($ids as $id) {
                            $record = $class::find($id);
                            if ($record) {
                                $record->delete();
                                $deleted++;
                            }
                        }
                    });
                    $softLabel = ($action['soft_delete'] ?? false) ? ' (soft-deleted)' : ' (permanently deleted)';
                    $results[] = "🗑️  Deleted {$deleted} {$modelName} record(s){$softLabel}";
                }
            }

            DB::commit();

            $html = '<div style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9);border-radius:12px;padding:16px;border-left:4px solid #4CAF50;">';
            $html .= '<div style="font-weight:700;color:#2E7D32;margin-bottom:8px;">✅ Action completed successfully</div>';
            $html .= '<ul style="margin:0;padding-left:20px;color:#333;">';
            foreach ($results as $r) {
                $html .= "<li style='margin:4px 0;'>{$r}</li>";
            }
            $html .= '</ul></div>';
            return ['html' => $html, 'affected_models' => array_unique($affectedModels)];

        } catch (\Exception $e) {
            DB::rollBack();
            $html = '<div style="background:#FFEBEE;border-radius:12px;padding:16px;border-left:4px solid #D32F2F;">'
                . '<strong style="color:#D32F2F;">❌ Error:</strong> ' . htmlspecialchars($e->getMessage())
                . '</div>';
            return ['html' => $html, 'affected_models' => []];
        }
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Model Discovery
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Auto-discover all Eloquent models with their table columns.
     */
    private function getModelMap(): array
    {
        if (self::$cachedModelMap)
            return self::$cachedModelMap;

        $modelPath = app_path('Models');
        $map = [];

        if (File::exists($modelPath)) {
            foreach (File::allFiles($modelPath) as $file) {
                $className = "App\\Models\\" . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $shortName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (!class_exists($className) || !is_subclass_of($className, Model::class))
                    continue;

                try {
                    $instance = new $className();
                    $table = $instance->getTable();
                    $columns = DB::getSchemaBuilder()->getColumnListing($table);
                    $hasSoftDelete = in_array(
                        \Illuminate\Database\Eloquent\SoftDeletes::class,
                        class_uses_recursive($className)
                    );

                    // Get detailed column info for AI
                    $columnDetails = [];
                    try {
                        $rawColumns = DB::select("SHOW COLUMNS FROM `{$table}`");
                        foreach ($rawColumns as $col) {
                            $columnDetails[$col->Field] = [
                                'type' => $col->Type,
                                'nullable' => $col->Null === 'YES',
                                'default' => $col->Default,
                                'key' => $col->Key, // PRI, MUL (foreign key), UNI
                            ];
                        }
                    } catch (\Throwable $e) {
                        // fallback: just column names
                    }

                    // Get fillable fields from model
                    $fillable = $instance->getFillable();

                    $map[$shortName] = [
                        'class' => $className,
                        'table' => $table,
                        'columns' => $columns,
                        'column_details' => $columnDetails,
                        'fillable' => $fillable,
                        'soft_delete' => $hasSoftDelete,
                    ];
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        self::$cachedModelMap = $map;
        return $map;
    }

    /**
     * Build a human-readable model map string for the AI prompt.
     */
    private function getModelMapString(): string
    {
        $map = $this->getModelMap();
        $output = "WRITABLE MODELS (use exact model name in the 'model' field):\n\n";

        foreach ($map as $name => $info) {
            if (in_array($name, self::PROTECTED_MODELS))
                continue;
            $sd = $info['soft_delete'] ? ' [soft-delete]' : ' [hard-delete]';
            $fillable = !empty($info['fillable']) ? implode(', ', $info['fillable']) : 'all';
            $output .= "— {$name} → table: {$info['table']}{$sd}\n";
            $output .= "  fillable: {$fillable}\n";

            // Show detailed column info
            if (!empty($info['column_details'])) {
                $output .= "  columns:\n";
                foreach ($info['column_details'] as $colName => $detail) {
                    if (in_array($colName, ['id', 'created_at', 'updated_at', 'deleted_at']))
                        continue;
                    $nullable = $detail['nullable'] ? 'nullable' : 'required';
                    $default = $detail['default'] !== null ? ", default={$detail['default']}" : '';
                    $fk = $detail['key'] === 'MUL' ? ' [FK]' : '';
                    $output .= "    - {$colName}: {$detail['type']} ({$nullable}{$default}){$fk}\n";
                }
            }
            $output .= "\n";
        }

        return $output;
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Confirmation & Extraction Helpers
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Build styled HTML confirmation card for the user.
     */
    private function buildConfirmationHtml(array $plan): string
    {
        $intent = $plan['intent'] ?? 'create';
        $isDelete = $intent === 'delete';
        $color = $isDelete ? '#D32F2F' : '#1976D2';
        $bgGradient = $isDelete ? 'linear-gradient(135deg,#FFEBEE,#FFCDD2)' : 'linear-gradient(135deg,#E3F2FD,#BBDEFB)';
        $icon = $isDelete ? '🗑️' : '📝';
        $title = $isDelete ? 'Delete Confirmation' : 'Create Confirmation';

        $html = "<div style='background:{$bgGradient};border-radius:12px;padding:16px;border-left:4px solid {$color};'>";
        $html .= "<div style='font-weight:700;color:{$color};margin-bottom:10px;font-size:14px;'>{$icon} {$title}</div>";

        // Show pre-create items
        if (!empty($plan['pre_create'])) {
            $html .= "<div style='margin-bottom:8px;color:#555;font-size:13px;'><strong>Dependencies to create first:</strong></div>";
            $html .= "<ul style='margin:0 0 8px 0;padding-left:20px;font-size:13px;'>";
            foreach ($plan['pre_create'] as $pc) {
                $fields = json_encode($pc['fields'] ?? [], JSON_UNESCAPED_UNICODE);
                $html .= "<li><strong>{$pc['model']}</strong>: {$fields}</li>";
            }
            $html .= "</ul>";
        }

        // Show main actions
        $html .= "<div style='margin-bottom:8px;color:#555;font-size:13px;'><strong>Actions:</strong></div>";
        $html .= "<ul style='margin:0 0 12px 0;padding-left:20px;font-size:13px;'>";
        foreach ($plan['actions'] as $action) {
            $model = $action['model'] ?? '?';
            if ($intent === 'create') {
                $count = count($action['records'] ?? []);
                $html .= "<li>Create <strong>{$count}</strong> {$model} record(s)</li>";
            } else {
                $count = count($action['ids'] ?? []);
                $softLabel = ($action['soft_delete'] ?? false) ? '(soft-delete, recoverable)' : '<span style="color:#D32F2F;font-weight:700;">(š ï¸ permanent!)</span>';
                $desc = $action['description'] ?? '';
                $html .= "<li>Delete <strong>{$count}</strong> {$model} record(s) {$softLabel}" . ($desc ? "  {$desc}" : '') . "</li>";
            }
        }
        $html .= "</ul>";

        // Show errors
        if (!empty($plan['errors'])) {
            $html .= "<div style='color:#EF6C00;font-size:12px;margin-bottom:8px;'>š ï¸ Warnings: " . implode(', ', $plan['errors']) . "</div>";
        }

        $html .= "<div style='font-size:13px;color:#333;'>Reply <strong>\"yes\"</strong> to confirm or <strong>\"no\"</strong> to cancel.</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Build cancel confirmation HTML.
     */
    private function buildCancelHtml(): string
    {
        return '<div style="background:#ECEFF1;border-radius:12px;padding:14px;border-left:4px solid #607D8B;">'
            . '<strong style="color:#455A64;">🚫 Action cancelled.</strong> No changes were made.'
            . '</div>';
    }

    /**
     * Extract pending action from conversation messages.
     */
    private function extractPendingAction(string $messagesJson): ?array
    {
        $messages = json_decode($messagesJson, true);
        if (!is_array($messages))
            return null;

        // Search backwards for the most recent pending action
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $msg = $messages[$i];
            $text = $msg['text'] ?? '';

            // Look for the hidden pending action div
            if (preg_match('/data-action=\'([^\']+)\'/', $text, $m)) {
                $decoded = json_decode(htmlspecialchars_decode($m[1], ENT_QUOTES), true);
                if (is_array($decoded) && isset($decoded['intent'])) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Build intent classification prompt.
     */
    private function buildIntentPrompt(string $question, string $messages, string $schema, string $modelMap): string
    {
        return <<<PROMPT
        === AVAILABLE MODELS ===
        {$modelMap}

        === DATABASE SCHEMA (for reference) ===
        {$schema}

        === CONVERSATION HISTORY ===
        {$messages}

        === USER MESSAGE ===
        {$question}

        === TASK ===
        You are an intent classifier. Classify the user's intent. Return ONLY valid JSON  nothing else.

        INTENT RULES:
        1. "read"  The user wants to QUERY, VIEW, LIST, COUNT, or ANALYZE existing data. Example: "how many charges?", "show suppliers", "total sales"
        2. "create"  The user wants to ADD, CREATE, INSERT, REGISTER, or ENTRY new records into the database. Example: "create a section", "add activity", "insert supplier", "entry workforce"
        3. "delete"  The user wants to DELETE, REMOVE, or SUPPRESS records. Example: "delete supplier 5", "remove this ship"
        4. "confirm"  The user is confirming a previous action proposal by saying: yes, ok, oui, confirm, go ahead, d'accord, proceed
        5. "cancel"  The user is cancelling by saying: no, cancel, stop, annuler

        CRITICAL: If the user uses words like "create", "add", "insert", "entry", "register", "new", "ajouter", "créer", "enregistrer"  the intent is ALWAYS "create", NEVER "read".
        CRITICAL: If the user uses words like "delete", "remove", "supprimer"  the intent is ALWAYS "delete", NEVER "read".
        CRITICAL: There is NO limit on the number of records. If the user asks to create 50, 100, 500 or more records, generate ALL of them in the "records" array. Never truncate or limit the output.

        CRITICAL TABLE STUDY RULES (for CREATE intent):
        Before generating any records, you MUST carefully study the AVAILABLE MODELS section above. Pay attention to:
        1. **Column types**: Use the correct data type for each field (string, integer, decimal, date, enum, etc.)
        2. **Required vs nullable**: If a column is "required", you MUST provide a value. If "nullable", you MAY omit it.
        3. **Enum columns**: Only use the allowed values listed in the type (e.g. enum('Q1','Q2') means only 'Q1' or 'Q2' are valid).
        4. **Foreign keys [FK]**: These columns reference another table's ID. Use only valid existing IDs or ask the user which FK value to use via "missing_info".
        5. **Default values**: If a column has a default, you can omit it and the database will use the default.
        6. **Only use fillable fields**: Only include fields listed in the "fillable" attribute. Never include id, created_at, updated_at, or deleted_at.
        7. **Decimal fields**: Use numeric values (not strings) for decimal/numeric columns.
        8. **Date fields**: Use 'YYYY-MM-DD' format for date columns.
        If you are unsure about a required field value, add it to "missing_info" array instead of guessing.

        For CREATE: use exact model names from the AVAILABLE MODELS list. Put ALL field values in "records" array. Generate every single record the user asks for. Example:
        {"intent":"create","actions":[{"model":"Section","records":[{"name":"test","daily_price":100}]}],"pre_create":[],"missing_info":[]}

        For bulk CREATE (e.g. "add 30 workforces"): generate all 30 records in the "records" array with realistic data.

        For DELETE: provide "ids" array if known, or "lookup" object to search by name. Example:
        {"intent":"delete","actions":[{"model":"Supplier","ids":[5],"description":"Supplier #5"}]}

        Return ONLY the JSON. No text before or after.
        PROMPT;
    }

    /**
     * Persist user and agent messages to the database.
     */
    private function persistMessages(Request $request, string $question, string $answer): void
    {
        $user = $request->user();
        if ($user) {
            ChatMessage::withoutEvents(function () use ($user, $question, $answer) {
                ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
                ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
            });
        }
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Prompt Builders
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Build a precise SQL generation prompt.
     */
    private function buildSQLPrompt(string $question, string $messages, string $schema, string $comments, string $businessCtx): string
    {
        return <<<PROMPT
        === DATABASE SCHEMA ===
        {$schema}

        === TABLE COMMENTS (purpose of each table) ===
        {$comments}

        === BUSINESS CONTEXT ===
        {$businessCtx}

        === RULES ===
        1. Generate ONLY a valid MySQL SELECT query that answers the user's question.
        2. Return ONLY the raw SQL  no markdown, no explanation, no backticks.
        3. Many tables use SoftDelete. Always add "WHERE deleted_at IS NULL" for tables that have a deleted_at column.
        4. Use proper JOINs based on the relationships described in the schema.
        5. For pivot/many-to-many tables (charge_suppliers, charge_ships, truck_charge, sell_clients, sell_ships, truck_sell), use JOIN through them.
        6. Format dates using DATE_FORMAT() when displaying dates.
        7. Use COALESCE for nullable fields to avoid NULL in results.
        8. LIMIT results to 500 rows max unless the user asks for all.
        9. For aggregations (SUM, COUNT, AVG), always include meaningful GROUP BY and aliases.
        10. The currency is MAD (Moroccan Dirham).
        11. Weight is measured in KG (kilograms).
        12. "charges" = purchases/incoming fish. "sells" = outgoing sales to clients.
        13. warehouse_stockable_tracking_number_operations is a polymorphic table (sourceable_type + sourceable_id) linking to both charges and sells.
        14. Fish types are in "fish_types" table, fish families in "fish_families" table.
        15. don't get users conversation history.

        === CONVERSATION HISTORY ===
        {$messages}

        === USER QUESTION ===
        {$question}
        PROMPT;
    }

    /**
     * Build the answer/analysis prompt from the retrieved data.
     */
    private function buildAnswerPrompt(string $question, string $messages, string $schema, string $comments, ?string $result, ?string $sqlError, string $businessCtx, string $routes): string
    {
        $dataSection = '';
        if ($sqlError) {
            $dataSection = "SQL Error occurred: {$sqlError}. Inform the user politely and suggest they rephrase.";
        } elseif ($result === null || $result === 'null' || $result === '[]') {
            $dataSection = "The database query returned NO results. Tell the user there is no data matching their question.";
        } else {
            $dataSection = "Database query results (use this as the SINGLE source of truth): {$result}";
        }

        return <<<PROMPT
        === YOUR ROLE ===
        You are "$this->app_name AI", a smart assistant for the $this->app_name fish management system.
        You help users understand their business data  purchases, sales, stock, suppliers, and operations.

        === BUSINESS CONTEXT ===
        {$businessCtx}

        === SCHEMA (for reference) ===
        {$schema}

        === TABLE COMMENTS ===
        {$comments}

        === APPLICATION ROUTES (all available pages) ===
        {$routes}

        === DATA ===
        {$dataSection}

        === RESPONSE RULES ===
        1. NEVER invent data. Only use the data provided above.
        2. Detect the user's language from their question and respond in the SAME language.
        3. Format numbers with spaces as thousands separator. CRITICAL FORMATTING RULES:
           - COUNTS (quantities, number of records, IDs, number of items, package count): display as INTEGERS with NO decimals. Example: 1 250 records, 35 items, ID #12.
           - PRICES (amounts, costs, unit_price, total_price, totals in MAD): display with exactly 2 decimals. Example: 12 500.00 MAD, 3 200.50 MAD.
           - WEIGHTS (total_weight, kg values): display with exactly 2 decimals. Example: 1 500.00 KG, 850.75 KG.
           - PERCENTAGES: display with 1 decimal. Example: 85.3%, 12.0%.
        4. For simple answers: use clean, plain text with key metrics highlighted in <strong> tags.
        5. For complex/tabular data: use a styled HTML <table> with:
           - Clean borders, padding, proper headers
           - Alternating row colors for readability
           - Right-aligned numbers
        6. For chart-worthy data (trends, comparisons): generate ApexCharts code in a <div>.
        7. Keep answers concise but complete. No unnecessary preamble.
        8. If the user asks a general question about the app (not data), answer based on your knowledge of the system.
        9. Always be helpful, professional, and friendly.
        10. don't get users conversation history.
        11. IMPORTANT NAV BUTTONS: Use the APPLICATION ROUTES map above to provide navigation buttons.
            - When discussing specific records (Charge, Sell, Invoice, etc.) or when the user asks "where can I...", "how do I go to...", "take me to...", ALWAYS provide a clickable button.
            - MUST use the class "ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1".
            - MUST include a "data-route" attribute with the exact path from the route map.
            - For routes with dynamic params like :id, substitute the real ID from the data.
            - Examples:
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/charges/view_charge/123">View Charge #123</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/charges/add_charge">Add New Charge</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/production/kanban">Production Kanban</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/parameters/fish-management">Fish Management</button>
            - Place buttons inline or inside table cells (e.g., an 'Action' column).
            - If the user asks for a page/link/direction, provide all relevant buttons from the route map.


        === CONVERSATION HISTORY ===
        {$messages}

        === USER QUESTION ===
        {$question}
        PROMPT;
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Business Context
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Get the business context of the application.
     * This gives the AI a deep understanding of the domain.
     */
    private function getBusinessContext(): string
    {
        if (self::$cachedBusinessContext)
            return self::$cachedBusinessContext;

        self::$cachedBusinessContext = <<<CTX
        APPLICATION: $this->app_name  Frozen Fish System
        DEVELOPER: ELBACHIR NOUBAA
        DOMAIN: Fish buying, processing, storage, and selling in Morocco.
        CURRENCY: MAD (Moroccan Dirham)
        WEIGHT UNIT: KG (Kilograms)

        KEY BUSINESS ENTITIES:
        - CHARGES (purchases): When the company buys fish from suppliers. Each charge has a date, supplier(s), ship, origin, fishery, tracking numbers, and line items.
        - SELLS (sales): When the company sells fish to clients. Similar structure with clients instead of suppliers.
        - WAREHOUSES: Storage locations with categories. Fish is tracked via warehouse_stockable_tracking_numbers.
        - SUPPLIERS: Fish suppliers the company buys from.
        - CLIENTS: Buyers the company sells to.
        - TRUCKS: Transport vehicles used for moving fish.
        - SHIPS: Fishing vessels.
        - FISHERIES: Fishing zones/ports.
        - ORIGINS: Geographic origin of the fish.
        - FISH TYPES: Specific fish species (sardine, mackerel, etc.)
        - FISH FAMILIES: Groups of fish types.
        - CHARGE CATEGORIES: Types of charges (some are "production" type  where is_production=true).
        - FORMULAS: Pricing/calculation formulas.
        - SUPPLIER INVOICES: Consolidated invoices grouping multiple charges for a supplier.
        - WAREHOUSE CATEGORY OPERATIONS: Types of warehouse operations (IN/OUT). Some have is_production=true meaning production processing.

        DATA FLOW:
        1. Fish arrives → recorded as a CHARGE (purchase)
        2. Fish enters warehouse → warehouse_stockable_tracking_number_operations (polymorphic, sourceable = Charge)
        3. Fish may be processed (production) → tracked via is_production flag on warehouse_category_operations
        4. Fish is sold → recorded as a SELL
        5. Fish leaves warehouse → warehouse_stockable_tracking_number_operations (polymorphic, sourceable = Sell)

        IMPORTANT TABLES:
        - warehouse_stockable_tracking_number_operations: This is the CORE operations table. It's polymorphic (sourceable_type = "App\\Models\\Charge" or "App\\Models\\Sell"). Contains unit_price, total_price, total_weight, total_package_count.
        - non_warehouse_stockable_operations: For items that don't go through warehouse (direct charges like services).
        CTX;

        return self::$cachedBusinessContext;
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Safety
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Check that the query is a safe read-only SELECT.
     */
    private function isSafeQuery(string $sql): bool
    {
        $normalized = strtoupper(trim($sql));
        $dangerous = ['INSERT ', 'UPDATE ', 'DELETE ', 'DROP ', 'ALTER ', 'TRUNCATE ', 'CREATE ', 'REPLACE ', 'GRANT ', 'REVOKE '];
        foreach ($dangerous as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return false;
            }
        }
        return str_starts_with($normalized, 'SELECT');
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Schema Extraction
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Get the full database schema as a JSON string (cached).
     */
    private function getSchemaString(): string
    {
        if (self::$cachedSchema)
            return self::$cachedSchema;

        $tables = DB::select("SHOW TABLES");
        $tableNames = array_map('current', $tables);
        $schema = [];

        foreach ($tableNames as $table) {
            // Skip migration/system tables
            if (in_array($table, ['migrations', 'password_resets', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs', 'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions'])) {
                continue;
            }
            $columns = DB::select("SHOW COLUMNS FROM `$table`");
            $schema[$table]['columns'] = array_map(fn($col) => [
                'Field' => $col->Field,
                'Type' => $col->Type,
                'Null' => $col->Null,
                'Key' => $col->Key,
            ], $columns);
        }

        // Add model relationships
        $models = $this->getAllModels();
        foreach ($models as $model) {
            try {
                $modelInstance = new $model();
                $table = $modelInstance->getTable();
                if (isset($schema[$table])) {
                    $schema[$table]['relationships'] = $this->getModelRelationships($modelInstance);
                    // Add soft delete info
                    $schema[$table]['uses_soft_delete'] = in_array(
                        \Illuminate\Database\Eloquent\SoftDeletes::class,
                        class_uses_recursive($model)
                    );
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        self::$cachedSchema = json_encode($schema, JSON_PRETTY_PRINT);
        return self::$cachedSchema;
    }

    /**
     * Get table comments as a string (cached).
     */
    private function getTableCommentsString(): string
    {
        if (self::$cachedComments)
            return self::$cachedComments;

        $database = DB::getDatabaseName();
        $comments = DB::table('information_schema.tables')
            ->select('table_name', 'table_comment')
            ->where('table_schema', $database)
            ->where('table_comment', '!=', '')
            ->get();

        self::$cachedComments = $comments->map(fn($c) => "{$c->table_name}: {$c->table_comment}")->implode("\n");
        return self::$cachedComments;
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Model/Relationship Introspection
    // """""""""""""""""""""""""""""""""""""""""

    public function getDatabaseSchema()
    {
        return response()->json(
            json_decode($this->getSchemaString(), true),
            Response::HTTP_OK,
            [],
            JSON_PRETTY_PRINT
        );
    }

    private function getAllModels(): array
    {
        $modelPath = app_path('Models');
        $models = [];
        if (File::exists($modelPath)) {
            foreach (File::allFiles($modelPath) as $file) {
                $className = "App\\Models\\" . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    $models[] = $className;
                }
            }
        }
        return $models;
    }

    private function getModelRelationships($modelInstance): array
    {
        $reflection = new ReflectionClass($modelInstance);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $relationships = [];
        foreach ($methods as $method) {
            if ($method->class === get_class($modelInstance) && $method->getNumberOfParameters() === 0) {
                try {
                    $relation = $method->invoke($modelInstance);
                    if ($relation instanceof Relation) {
                        $relatedModel = class_basename($relation->getRelated());
                        $relationType = class_basename(get_class($relation));
                        $relationships[$method->name] = "{$relationType} → {$relatedModel}";
                    }
                } catch (\Throwable $e) {
                    // Skip
                }
            }
        }
        return $relationships;
    }

    public function getTableComments()
    {
        $database = DB::getDatabaseName();
        return DB::table('information_schema.tables')
            ->select('table_name', 'table_comment')
            ->where('table_schema', $database)
            ->get();
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Application Route Map (auto-synced from routes.js)
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Dynamically parse the frontend routes.js file to build
     * a structured route map. Auto-syncs when routes.js changes.
     * Cached per-request for performance.
     */
    private function getAppRoutes(): string
    {
        if (self::$cachedRoutes)
            return self::$cachedRoutes;

        $routesFile = resource_path('js/router/routes.js');
        if (!File::exists($routesFile)) {
            self::$cachedRoutes = 'No route map available.';
            return self::$cachedRoutes;
        }

        $content = File::get($routesFile);
        $routes = $this->parseJsRoutes($content);

        // Filter out system/auth routes the AI should not suggest
        $skipNames = ['error', 'error404', 'NotFound', 'auth', 'login'];

        $output = "AVAILABLE APPLICATION PAGES (use these paths for navigation buttons):\n\n";
        foreach ($routes as $r) {
            if (in_array($r['name'], $skipNames))
                continue;
            $label = ucwords(str_replace('_', ' ', $r['name']));
            $paramHint = str_contains($r['path'], ':') ? ' (replace dynamic params with actual values)' : '';
            $output .= "- {$r['path']} → {$label}{$paramHint}\n";
        }

        self::$cachedRoutes = $output;
        return self::$cachedRoutes;
    }

    /**
     * Parse JavaScript routes file to extract route path/name pairs.
     * Handles nested children and resolves relative paths.
     */
    private function parseJsRoutes(string $content): array
    {
        // Remove function bodies to avoid false bracket counts
        $content = $this->stripJsFunctionBodies($content);

        $routes = [];
        $pathStack = [];
        $childrenDepthMarkers = [];
        $depth = 0;
        $lastPath = null;

        foreach (explode("\n", $content) as $line) {
            $t = trim($line);
            if ($t === '' || str_starts_with($t, 'import ') || str_starts_with($t, 'export '))
                continue;

            // Detect children: [ → push current path onto stack
            if (preg_match('/children\s*:\s*\[/', $t)) {
                $pathStack[] = $lastPath ?? '';
                $childrenDepthMarkers[] = $depth;
            }

            // Extract path value
            if (preg_match('/path:\s*["\']([^"\']*)["\']/', $t, $m)) {
                $lastPath = $m[1];
            }

            // Extract route name (skip redirect objects)
            if (preg_match('/name:\s*["\']([^"\']*)["\']/', $t, $m) && !str_contains($t, 'redirect')) {
                $routes[] = [
                    'path' => $this->resolveRoutePath($pathStack, $lastPath),
                    'name' => $m[1],
                ];
            }

            // Track bracket/brace depth
            $depth += substr_count($t, '[') + substr_count($t, '{');
            $depth -= substr_count($t, ']') + substr_count($t, '}');

            // Pop children stack when depth returns to marker level
            while (!empty($childrenDepthMarkers) && $depth <= end($childrenDepthMarkers)) {
                array_pop($pathStack);
                array_pop($childrenDepthMarkers);
            }
        }

        return $routes;
    }

    /**
     * Resolve a route path against its parent path stack.
     * Absolute paths (starting with /) are returned as-is.
     * Relative paths are appended to the nearest parent absolute path.
     */
    private function resolveRoutePath(array $parentPaths, ?string $currentPath): string
    {
        if ($currentPath === null)
            return '/';
        if (str_starts_with($currentPath, '/'))
            return $currentPath;

        $base = '';
        foreach ($parentPaths as $pp) {
            if (str_starts_with($pp, '/')) {
                $base = rtrim($pp, '/');
            } else {
                $base = rtrim($base, '/') . '/' . $pp;
            }
        }

        return rtrim($base, '/') . '/' . $currentPath;
    }

    /**
     * Remove JavaScript function bodies from content.
     * This prevents function braces from interfering with route bracket counting.
     */
    private function stripJsFunctionBodies(string $content): string
    {
        $result = '';
        $len = strlen($content);
        $i = 0;

        while ($i < $len) {
            if ($i + 8 <= $len && substr($content, $i, 8) === 'function') {
                // Find opening brace
                $j = $i + 8;
                while ($j < $len && $content[$j] !== '{')
                    $j++;
                if ($j >= $len) {
                    $result .= substr($content, $i);
                    break;
                }
                // Count braces to find matching close
                $braces = 1;
                $k = $j + 1;
                while ($k < $len && $braces > 0) {
                    if ($content[$k] === '{')
                        $braces++;
                    elseif ($content[$k] === '}')
                        $braces--;
                    $k++;
                }
                $i = $k;
                continue;
            }
            $result .= $content[$i];
            $i++;
        }

        return $result;
    }
}


