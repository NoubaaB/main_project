<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChatMessage;
use App\Services\GeminiService;
use App\Services\AIAnalyticsService;
use App\Events\ModelChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AIAssistantController extends Controller
{
    private GeminiService $ai;

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
        'ChatMessage',
    ];

    public function __construct()
    {
        $this->ai = new GeminiService();
        $this->app_name = env('APP_NAME');
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Main endpoint: POST /api/ai-assistant
    // """""""""""""""""""""""""""""""""""""""""
    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'messages' => 'nullable',
            'theme' => 'nullable|string',
            'model' => 'nullable|string',
            'locale' => 'nullable|string',
        ]);

        $startTime = microtime(true);
        $question = $data['message'];
        $messagesRaw = $data['messages'];
        $messages = is_array($messagesRaw) ? json_encode($messagesRaw) : $messagesRaw;
        $thinkingLevel = $request->input('thinking_level', 'high');
        $theme = in_array(strtolower((string) $request->input('theme', 'dark')), ['dark', 'light']) ? strtolower((string) $request->input('theme', 'dark')) : 'dark';
        $requestedModel = $request->input('model');
        $uiLocale = $request->input('locale', $request->input('lang', 'en'));

        // Enforce user AI model permissions (Super admin has all models by default)
        $user = $request->user();
        if ($user) {
            if (!empty($requestedModel) && !$user->isModelAllowed($requestedModel)) {
                $requestedModel = $user->getDefaultAIModel();
            } elseif (empty($requestedModel)) {
                $requestedModel = $user->getDefaultAIModel();
            }
        }

        if (!empty($requestedModel) && method_exists($this->ai, 'setModel')) {
            $this->ai->setModel($requestedModel);
        }

        $this->ai->setThinkingLevel($thinkingLevel);
        $analytics = new AIAnalyticsService();
        $detectedLang = $analytics->detectLanguage($question, $uiLocale);

        // Check if asking about System Architecture & AI Core
        $lowerQuestion = mb_strtolower($question, 'UTF-8');
        if (preg_match('/(creator|created|owner|supervisor|superviseur|elbachir|noubaa|bachir|développeur|qui t\'a|qui a créé|qui est ton|de qui es-tu|quien te creo|من صنعك|البشير|النوباع)/ui', $lowerQuestion)) {
            $answer = $analytics->processQuestion($question, 'asst_' . time(), $theme, [], $detectedLang);
            $thinkingDuration = round(microtime(true) - $startTime, 2);
            $reasoning = $this->buildReasoningPayload($question, 'system_identity', null, [], $thinkingDuration);
            $this->persistMessages($request, $question, $answer, $reasoning);
            $usedModel = $requestedModel ?: (method_exists($this->ai, 'getLastUsedModel') ? $this->ai->getLastUsedModel() : 'gemini-4-carbon');
            return response()->json([
                'answer' => $answer,
                'reasoning' => $reasoning,
                'thinking_time' => $thinkingDuration,
                'model' => $usedModel,
                'message' => [
                    'id' => 'msg_' . uniqid(),
                    'role' => 'assistant',
                    'content' => $answer,
                    'reasoning' => $reasoning,
                    'model' => $usedModel,
                    'created_at' => now()->toIso8601String(),
                ]
            ], 200);
        }

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

        // "" Handle WRITE/UPDATE/DELETE intents ""
        if (in_array($intent, ['create', 'update', 'delete'])) {
            return $this->handleWriteIntent($request, $question, $messages, $intentData, $schema, $tableComments, $businessCtx, $theme);
        }

        if ($intent === 'confirm') {
            return $this->handleConfirmIntent($request, $question, $messages, $schema, $tableComments, $businessCtx, $theme);
        }

        if ($intent === 'cancel') {
            $answer = $this->buildCancelHtml($theme);
            $thinkingDuration = round(microtime(true) - $startTime, 2);
            $reasoning = $this->buildReasoningPayload($question, 'cancel', null, [], $thinkingDuration);
            $this->persistMessages($request, $question, $answer, $reasoning);
            return response()->json(['answer' => $answer, 'reasoning' => $reasoning, 'thinking_time' => $thinkingDuration], 200);
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

        // Check for previously uploaded Excel files the user might be referencing
        $excelContext = $this->getExcelContextFromHistory($request);
        $answerPrompt = $this->buildAnswerPrompt($question, $messages, $schema, $tableComments, $result, $sqlError, $businessCtx, $routes, $excelContext, $theme, $sqlQuery, $detectedLang);
        $rawAnswer = $this->ai->generateAnswer($answerPrompt);
        $answer = null;
        $rows = $result ? (json_decode($result, true) ?: []) : [];
        if ($rawAnswer) {
            $answer = $this->beautifyAndValidateAIAnswer($rawAnswer, $question, $rows, 'asst_' . time(), $theme, $detectedLang);
        }

        if (!$answer || !$this->isAnswerSufficientlyPolished($answer, $rows)) {
            $answer = $analytics->processQuestion($question, 'asst_' . time(), $theme, [], $detectedLang);
        }

        $thinkingDuration = round(microtime(true) - $startTime, 2);
        $reasoning = $this->buildReasoningPayload($question, $intent, $sqlQuery, $rows, $thinkingDuration);

        $this->persistMessages($request, $question, $answer, $reasoning);

        $usedModel = $requestedModel ?: (method_exists($this->ai, 'getLastUsedModel') ? $this->ai->getLastUsedModel() : 'gemini-4-carbon');

        return response()->json([
            'answer' => $answer,
            'result' => $result,
            'sqlQuery' => $sqlQuery,
            'reasoning' => $reasoning,
            'thinking_time' => $thinkingDuration,
            'model' => $usedModel,
            'message' => [
                'id' => 'msg_' . uniqid(),
                'role' => 'assistant',
                'content' => $answer,
                'reasoning' => $reasoning,
                'model' => $usedModel,
                'created_at' => now()->toIso8601String(),
            ]
        ], 200);
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Write/Delete Intent Handlers
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Handle create/delete intents: validate, build confirmation, return to user.
     */
    private function handleWriteIntent(Request $request, string $question, string $messages, array $intentData, string $schema, string $tableComments, string $businessCtx, string $theme = 'dark')
    {
        $intent = $intentData['intent'];
        $actions = $intentData['actions'] ?? [];
        $preCreate = $intentData['pre_create'] ?? [];
        $missingInfo = $intentData['missing_info'] ?? [];
        $modelMap = $this->getModelMap();
        $errors = [];
        $validatedActions = [];

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

            if ($intent === 'update') {
                $records = $action['records'] ?? [];
                $validatedRecords = [];
                $columns = $modelMap[$modelName]['columns'];

                \Illuminate\Support\Facades\Log::info("AI Update intent for model={$modelName}. Records count=" . count($records) . ", SQL=" . ($action['sql'] ?? 'none') . ", Raw records=" . json_encode($records));

                foreach ($records as $record) {
                    $cleaned = [];
                    foreach ($record as $field => $value) {
                        if ($field === 'id' || in_array($field, $columns)) {
                            $cleaned[$field] = $value;
                        }
                    }
                    // Must have an id to update
                    if (!empty($cleaned) && isset($cleaned['id'])) {
                        $validatedRecords[] = $cleaned;
                    }
                }

                // If no records with IDs, try to resolve
                if (empty($validatedRecords)) {
                    // Extract the update fields from records (without id)
                    $updateFields = [];
                    foreach ($records as $record) {
                        foreach ($record as $field => $value) {
                            if ($field !== 'id' && in_array($field, $columns)) {
                                $updateFields[$field] = $value;
                            }
                        }
                        if (!empty($updateFields))
                            break;
                    }

                    // Fetch all IDs from the model and build update records
                    $class = $modelMap[$modelName]['class'];
                    $allIds = $class::pluck('id')->toArray();

                    if (!empty($updateFields) && !empty($allIds)) {
                        foreach ($allIds as $id) {
                            $rec = array_merge(['id' => $id], $updateFields);
                            $validatedRecords[] = $rec;
                        }
                        $action['description'] = ($action['description'] ?? "Update $modelName") . " (all " . count($allIds) . " records)";
                        $action['records'] = $validatedRecords;
                        \Illuminate\Support\Facades\Log::info("AI Update: Built " . count($validatedRecords) . " bulk update records for {$modelName}");
                    }
                    // If we have SQL but no update fields from records, estimate count from table
                    elseif (!empty($action['sql'])) {
                        $estimatedCount = count($allIds);
                        $action['records'] = [];
                        $action['estimated_count'] = $estimatedCount;
                        \Illuminate\Support\Facades\Log::info("AI Update: SQL-based update for {$modelName}, estimated {$estimatedCount} records");
                    } else {
                        $errors[] = "No valid records with IDs found to update for $modelName.";
                        continue;
                    }
                } else {
                    $action['records'] = $validatedRecords;
                }
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

                // If still no IDs and no lookup, user wants to delete ALL records
                if (empty($action['ids']) && empty($lookup)) {
                    $class = $modelMap[$modelName]['class'];
                    $allIds = $class::pluck('id')->toArray();
                    if (!empty($allIds)) {
                        $action['ids'] = $allIds;
                        $action['description'] = ($action['description'] ?? '') . " (all " . count($allIds) . " records)";
                    }
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
            $validatedPreCreate[] = $pc;
        }

        if (!empty($errors) && empty($validatedActions)) {
            $answer = '<div style="padding:8px;color:#D32F2F;"><strong>⚠️ Cannot execute:</strong><ul>' .
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

        $confirmHtml = $this->buildConfirmationHtml($pendingAction, $theme);

        // Store pending action as a special hidden tag in the answer
        // Use base64_encode to avoid any HTML quote escaping issues with complex SQL queries
        $pendingJson = base64_encode(json_encode($pendingAction));
        $answer = $confirmHtml . '<div class="ai-pending-action" style="display:none" data-action="' . $pendingJson . '"></div>';

        $this->persistMessages($request, $question, $answer);
        return response()->json(['answer' => $answer, 'pending_action' => $pendingAction], 200);
    }

    /**
     * Handle user confirmation  find the pending action and execute it.
     */
    private function handleConfirmIntent(Request $request, string $question, string $messages, string $schema, string $tableComments, string $businessCtx, string $theme = 'dark')
    {
        // Find pending action in conversation history
        $pendingAction = $this->extractPendingAction($messages);

        if (!$pendingAction) {
            $answer = "I don't have a pending action to confirm. What would you like me to do?";
            $this->persistMessages($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // Execute the action plan
        $result = $this->executeActionPlan($pendingAction, $theme);
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
            'theme' => 'nullable|string',
        ]);

        $actionPlan = $data['action_plan'];
        $theme = in_array(strtolower((string) $request->input('theme', 'dark')), ['dark', 'light']) ? strtolower((string) $request->input('theme', 'dark')) : 'dark';
        $user = $request->user();
        $intent = $actionPlan['intent'] ?? 'create';

        // Only block protected models
        foreach ($actionPlan['actions'] ?? [] as $action) {
            $modelName = $action['model'] ?? '';
            if (in_array($modelName, self::PROTECTED_MODELS)) {
                $error = "Model '$modelName' is protected and cannot be modified by the AI.";
                return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;"><strong>⚠️ ' . $error . '</strong></div>'], 403);
            }
        }
        foreach ($actionPlan['pre_create'] ?? [] as $pc) {
            $pcModel = $pc['model'] ?? '';
            if (in_array($pcModel, self::PROTECTED_MODELS)) {
                $error = "Model '$pcModel' is protected and cannot be modified by the AI.";
                return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;"><strong>⚠️ ' . $error . '</strong></div>'], 403);
            }
        }

        $result = $this->executeActionPlan($actionPlan, $theme);

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
     * The raw file is also uploaded for storage and future reference.
     */
    public function askWithExcel(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'excel_data' => 'required|string', // JSON string from FormData
            'theme' => 'nullable|string',
            'file' => 'nullable|file|max:10240|mimes:xlsx,xls,csv',
        ]);

        $theme = in_array(strtolower((string) $request->input('theme', 'dark')), ['dark', 'light']) ? strtolower((string) $request->input('theme', 'dark')) : 'dark';

        // Parse excel_data from JSON string (FormData sends as string)
        $excelData = json_decode($request->input('excel_data'), true);
        if (!is_array($excelData) || empty($excelData['headers']) || empty($excelData['rows'])) {
            return response()->json(['answer' => '<div style="padding:8px;color:#D32F2F;">Invalid Excel data format.</div>'], 422);
        }

        $message = $request->input('message');
        $headers = $excelData['headers'] ?? [];
        $rows = $excelData['rows'] ?? [];
        $filename = $excelData['filename'] ?? 'uploaded_file.xlsx';
        $sheetName = $excelData['sheet_name'] ?? 'Sheet1';

        // Smart header detection: if headers look like a title row (only 1 non-empty cell),
        // scan the first data rows to find the real header row
        $nonEmptyHeaders = array_filter($headers, fn($h) => !empty(trim((string) $h)));
        if (count($nonEmptyHeaders) <= 1 && count($rows) > 0) {
            \Illuminate\Support\Facades\Log::info("Excel: Detected title row in headers, scanning data rows for real headers...");
            for ($ri = 0; $ri < min(count($rows), 10); $ri++) {
                $candidateRow = $rows[$ri];
                $nonEmpty = array_filter($candidateRow, fn($c) => !empty(trim((string) ($c ?? ''))));
                if (count($nonEmpty) >= 2) {
                    // This row looks like real headers
                    $headers = array_map(fn($h) => trim((string) ($h ?? '')), $candidateRow);
                    // Remove empty trailing headers
                    while (count($headers) > 0 && empty($headers[count($headers) - 1])) {
                        array_pop($headers);
                    }
                    // Remove this row and all rows before it from data
                    $rows = array_slice($rows, $ri + 1);
                    // Also filter out empty rows
                    $rows = array_filter($rows, fn($r) => count(array_filter($r, fn($c) => !empty(trim((string) ($c ?? ''))))) > 0);
                    $rows = array_values($rows);
                    \Illuminate\Support\Facades\Log::info("Excel: Found real headers at data row {$ri}: " . json_encode($headers));
                    break;
                }
            }
        }

        $totalRows = count($rows);

        // Store the uploaded file if provided
        $filePath = null;
        $fileUrl = null;
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $storedName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $uploadedFile->getClientOriginalName());
            $filePath = $uploadedFile->storeAs('chat_excels', $storedName, 'public');
            $fileUrl = asset('storage/' . $filePath);
        }

        // Build a text representation of the Excel data
        $excelText = "File: {$filename} | Sheet: {$sheetName} | Total Rows: {$totalRows}\n";
        $excelText .= "Headers: " . implode(' | ', $headers) . "\n\n";
        foreach ($rows as $i => $row) {
            $values = array_map(function ($v) {
                return is_null($v) ? '' : (string) $v;
            }, $row);
            $excelText .= "Row " . ($i + 1) . ": " . implode(' | ', $values) . "\n";
        }

        // Detect intent: does the user want to INSERT, UPDATE, DELETE, or ANALYZE?
        $schema = $this->getSchemaString();
        $modelMapStr = $this->getModelMapString();
        $fkLookup = $this->buildForeignKeyLookupTable();

        $prompt = $this->buildExcelPrompt($message, $excelText, $schema, $modelMapStr, $headers, $totalRows, $fkLookup);

        $this->ai = new GeminiService();
        if ($request->has('thinking_level')) {
            $this->ai->setThinkingLevel($request->input('thinking_level', 'high'));
        }
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
            // Detect if user wants to simply DISPLAY/SHOW all data — build table directly, no AI truncation
            $displayKeywords = ['display', 'show', 'afficher', 'montrer', 'voir', 'list all', 'show all', 'display all', 'afficher tout', 'montrer tout', 'show data', 'display data', 'afficher les données', 'montrer les données', 'all data', 'toutes les données'];
            $msgLower = strtolower($message);
            $isDisplayRequest = false;
            foreach ($displayKeywords as $kw) {
                if (str_contains($msgLower, $kw)) {
                    $isDisplayRequest = true;
                    break;
                }
            }

            if ($isDisplayRequest) {
                // Build HTML table directly — no AI involvement, no truncation
                $answer = $this->buildExcelDisplayHtml($headers, $rows, $filename, $totalRows);
            } else {
                $analysisPrompt = <<<PROMPT
                === EXCEL DATA ===
                {$excelText}

                === USER REQUEST ===
                {$message}

                === TASK ===
                Analyze the Excel data above based on the user's request. 
                - If the user wants a summary, provide a clear summary with key statistics.
                - If the user wants to search, find matching rows and present them clearly.
                - CRITICAL: You MUST show ALL data rows. NEVER truncate, shorten, summarize, or use "..." or ellipsis. Display EVERY SINGLE ROW from the Excel data.
                - Format your response as clean HTML with inline styles.
                - Use styled tables if showing data — include ALL rows, not a subset.
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
            }

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $answer, $filePath) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}", 'file_path' => $filePath, 'file_name' => $filename]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
                });
            }

            return response()->json(['answer' => $answer, 'file_url' => $fileUrl, 'file_name' => $filename], 200);
        }

        // Get FK lookup array for resolving names → IDs in records
        $fkLookupArray = $this->getForeignKeyLookupArray();

        // Handle CREATE intent — propose an action plan to insert Excel rows into DB
        if ($intent === 'create') {
            $actions = $parsed['actions'] ?? [];
            if (empty($actions)) {
                return response()->json(['answer' => '<div style="padding:8px;color:#FF9800;">Could not determine which model to insert into.</div>'], 200);
            }

            // Map Excel rows to model records based on AI's field mapping
            $mappedActions = [];
            $allUnresolved = [];
            foreach ($actions as $action) {
                $modelName = $action['model'] ?? '';
                $fieldMapping = $action['field_mapping'] ?? [];
                $records = [];

                \Illuminate\Support\Facades\Log::info("Excel CREATE: model={$modelName}, field_mapping=" . json_encode($fieldMapping) . ", headers=" . json_encode($headers) . ", rows_count=" . count($rows));

                if (!empty($fieldMapping)) {
                    // Build case-insensitive header index for robust matching
                    $headerIndexMap = [];
                    foreach ($headers as $idx => $h) {
                        $headerIndexMap[strtolower(trim((string) $h))] = $idx;
                    }

                    foreach ($rows as $row) {
                        $rec = [];
                        foreach ($fieldMapping as $excelCol => $dbField) {
                            // Try exact match first
                            $colIdx = array_search($excelCol, $headers);
                            // Then case-insensitive
                            if ($colIdx === false) {
                                $colIdx = $headerIndexMap[strtolower(trim((string) $excelCol))] ?? false;
                            }
                            if ($colIdx !== false && isset($row[$colIdx]) && $row[$colIdx] !== '') {
                                $rec[$dbField] = $row[$colIdx];
                            }
                        }
                        if (!empty($rec)) {
                            $records[] = $rec;
                        }
                    }

                    \Illuminate\Support\Facades\Log::info("Excel CREATE: AI field_mapping produced " . count($records) . " records");
                }

                // FALLBACK: if AI field_mapping produced 0 records, try automatic header-to-column matching
                if (empty($records) && !empty($rows)) {
                    \Illuminate\Support\Facades\Log::warning("Excel CREATE: AI field_mapping failed, attempting automatic column matching for model={$modelName}");

                    $modelMap = $this->getModelMap();
                    $modelInfo = $modelMap[$modelName] ?? null;
                    $fillableColumns = [];

                    if ($modelInfo) {
                        try {
                            $modelInstance = new $modelInfo['class'];
                            $fillableColumns = $modelInstance->getFillable();
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error("Excel CREATE: Could not get fillable columns: " . $e->getMessage());
                        }
                    }

                    if (!empty($fillableColumns)) {
                        // Build a mapping: try to match each Excel header to a fillable column
                        $autoMapping = [];
                        $headerIndexMap = [];
                        foreach ($headers as $idx => $h) {
                            $headerIndexMap[strtolower(trim((string) $h))] = $idx;
                        }

                        foreach ($fillableColumns as $col) {
                            $colLower = strtolower($col);
                            // Try direct match (header == column name)
                            if (isset($headerIndexMap[$colLower])) {
                                $autoMapping[$headers[$headerIndexMap[$colLower]]] = $col;
                                continue;
                            }
                            // Try without underscores (e.g., "fullname" matches "full_name")
                            $colNoUnderscore = str_replace('_', '', $colLower);
                            foreach ($headerIndexMap as $hLower => $hIdx) {
                                $hNoUnderscore = str_replace(['_', ' ', '-'], '', $hLower);
                                if ($hNoUnderscore === $colNoUnderscore) {
                                    $autoMapping[$headers[$hIdx]] = $col;
                                    break;
                                }
                            }
                            // Try partial match for common patterns (e.g., "nom" in "full_name")
                            if (!isset($autoMapping[$col])) {
                                $commonAliases = [
                                    'full_name' => ['name', 'nom', 'full name', 'fullname', 'nom complet', 'nombre'],
                                    'matricule' => ['matricule', 'mat', 'registration'],
                                    'nationality' => ['nationality', 'nationalité', 'nationalite', 'nation'],
                                    'sex' => ['sex', 'sexe', 'gender', 'genre'],
                                    'cni' => ['cni', 'cin', 'id card', 'carte', 'identity'],
                                    'is_migrant' => ['migrant', 'is_migrant', 'ismigrant'],
                                    'salary_statement' => ['salary_statement', 'salary statement', 'salaire'],
                                    'fixed_daily_pay' => ['fixed_daily_pay', 'fixed daily pay', 'fixe'],
                                    'cnss_id' => ['cnss', 'cnss_id', 'social security'],
                                    'daily_price' => ['daily_price', 'daily price', 'prix', 'price', 'salaire journalier', 'prix journalier'],
                                ];
                                $aliases = $commonAliases[$col] ?? [];
                                foreach ($aliases as $alias) {
                                    $aliasLower = strtolower($alias);
                                    if (isset($headerIndexMap[$aliasLower])) {
                                        $autoMapping[$headers[$headerIndexMap[$aliasLower]]] = $col;
                                        break;
                                    }
                                }
                            }
                        }

                        \Illuminate\Support\Facades\Log::info("Excel CREATE: Auto-mapping result: " . json_encode($autoMapping));

                        if (!empty($autoMapping)) {
                            foreach ($rows as $row) {
                                $rec = [];
                                foreach ($autoMapping as $excelCol => $dbField) {
                                    $colIdx = array_search($excelCol, $headers);
                                    if ($colIdx !== false && isset($row[$colIdx]) && $row[$colIdx] !== '') {
                                        $rec[$dbField] = $row[$colIdx];
                                    }
                                }
                                if (!empty($rec)) {
                                    $records[] = $rec;
                                }
                            }
                            \Illuminate\Support\Facades\Log::info("Excel CREATE: Auto-mapping produced " . count($records) . " records");
                        }
                    }
                }

                // Last resort fallback: if still 0 records and AI provided records directly
                if (empty($records) && !empty($action['records'])) {
                    $records = $action['records'];
                    \Illuminate\Support\Facades\Log::info("Excel CREATE: Using AI-provided records directly, count=" . count($records));
                }

                // Resolve foreign keys: replace names with real IDs
                $resolved = $this->resolveForeignKeysInRecords($records, $fkLookupArray);
                $records = $resolved['records'];
                $allUnresolved = array_merge($allUnresolved, $resolved['unresolved']);

                $mappedActions[] = [
                    'model' => $modelName,
                    'records' => $records,
                ];
            }

            $totalRecords = array_sum(array_map(fn($a) => count($a['records']), $mappedActions));

            // Build confirmation HTML
            $pendingAction = [
                'intent' => 'create',
                'actions' => $mappedActions,
                'pre_create' => $parsed['pre_create'] ?? [],
            ];
            $html = $this->buildConfirmationHtml($pendingAction);
            $pendingJson = base64_encode(json_encode($pendingAction));
            $html .= '<div class="ai-pending-action" style="display:none" data-action="' . $pendingJson . '"></div>';

            // Add unresolved FK warnings
            if (!empty($allUnresolved)) {
                $html .= '<div style="background:#FFF3E0;border-radius:8px;padding:10px;margin-top:8px;border-left:3px solid #FF9800;font-size:12px;">';
                $html .= '<strong style="color:#E65100;">⚠️ Some foreign keys could not be resolved:</strong><ul style="margin:4px 0 0 16px;">';
                foreach (array_unique($allUnresolved) as $warn) {
                    $html .= '<li>' . htmlspecialchars($warn) . '</li>';
                }
                $html .= '</ul></div>';
            }

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $html, $filePath) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}", 'file_path' => $filePath, 'file_name' => $filename]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $html]);
                });
            }

            return response()->json([
                'answer' => $html,
                'pending_action' => $pendingAction,
                'file_url' => $fileUrl,
                'file_name' => $filename,
            ], 200);
        }

        // Handle UPDATE intent — map Excel rows to update records
        if ($intent === 'update') {
            $actions = $parsed['actions'] ?? [];
            if (empty($actions)) {
                return response()->json(['answer' => '<div style="padding:8px;color:#FF9800;">Could not determine which model to update.</div>'], 200);
            }

            $mappedActions = [];
            $allUnresolved = [];
            foreach ($actions as $action) {
                $modelName = $action['model'] ?? '';
                $fieldMapping = $action['field_mapping'] ?? [];
                $idColumn = $action['id_column'] ?? null;
                $records = [];

                if (!empty($fieldMapping)) {
                    // Build case-insensitive header index for robust matching
                    $headerIndexMap = [];
                    foreach ($headers as $idx => $h) {
                        $headerIndexMap[strtolower(trim($h))] = $idx;
                    }

                    foreach ($rows as $row) {
                        $rec = [];
                        foreach ($fieldMapping as $excelCol => $dbField) {
                            // Try exact match first, then case-insensitive
                            $colIdx = array_search($excelCol, $headers);
                            if ($colIdx === false) {
                                $colIdx = $headerIndexMap[strtolower(trim($excelCol))] ?? false;
                            }
                            if ($colIdx !== false && isset($row[$colIdx]) && $row[$colIdx] !== '') {
                                $rec[$dbField] = $row[$colIdx];
                            }
                        }
                        // Try to get the ID from the id_column (also case-insensitive)
                        if ($idColumn) {
                            $idIdx = array_search($idColumn, $headers);
                            if ($idIdx === false) {
                                $idIdx = $headerIndexMap[strtolower(trim($idColumn))] ?? false;
                            }
                            if ($idIdx !== false && isset($row[$idIdx])) {
                                $rec['id'] = intval($row[$idIdx]);
                            }
                        }
                        if (!empty($rec) && isset($rec['id'])) {
                            $records[] = $rec;
                        }
                    }
                } elseif (!empty($action['records'])) {
                    $records = $action['records'];
                }

                // Resolve foreign keys
                $resolved = $this->resolveForeignKeysInRecords($records, $fkLookupArray);
                $records = $resolved['records'];
                $allUnresolved = array_merge($allUnresolved, $resolved['unresolved']);

                $mappedActions[] = [
                    'model' => $modelName,
                    'records' => $records,
                ];
            }

            $pendingAction = [
                'intent' => 'update',
                'actions' => $mappedActions,
            ];
            $html = $this->buildConfirmationHtml($pendingAction);
            $pendingJson = base64_encode(json_encode($pendingAction));
            $html .= '<div class="ai-pending-action" style="display:none" data-action="' . $pendingJson . '"></div>';

            if (!empty($allUnresolved)) {
                $html .= '<div style="background:#FFF3E0;border-radius:8px;padding:10px;margin-top:8px;border-left:3px solid #FF9800;font-size:12px;">';
                $html .= '<strong style="color:#E65100;">⚠️ Some foreign keys could not be resolved:</strong><ul style="margin:4px 0 0 16px;">';
                foreach (array_unique($allUnresolved) as $warn) {
                    $html .= '<li>' . htmlspecialchars($warn) . '</li>';
                }
                $html .= '</ul></div>';
            }

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $html, $filePath) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}", 'file_path' => $filePath, 'file_name' => $filename]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $html]);
                });
            }

            return response()->json([
                'answer' => $html,
                'pending_action' => $pendingAction,
                'file_url' => $fileUrl,
                'file_name' => $filename,
            ], 200);
        }

        // Handle DELETE intent — identify records to delete
        if ($intent === 'delete') {
            $actions = $parsed['actions'] ?? [];
            if (empty($actions)) {
                return response()->json(['answer' => '<div style="padding:8px;color:#FF9800;">Could not determine which records to delete.</div>'], 200);
            }

            $pendingAction = [
                'intent' => 'delete',
                'actions' => $actions,
            ];
            $html = $this->buildConfirmationHtml($pendingAction);
            $pendingJson = base64_encode(json_encode($pendingAction));
            $html .= '<div class="ai-pending-action" style="display:none" data-action="' . $pendingJson . '"></div>';

            $user = $request->user();
            if ($user) {
                ChatMessage::withoutEvents(function () use ($user, $filename, $message, $html, $filePath) {
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => "📊 [Excel: {$filename}] {$message}", 'file_path' => $filePath, 'file_name' => $filename]);
                    ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $html]);
                });
            }

            return response()->json([
                'answer' => $html,
                'pending_action' => $pendingAction,
                'file_url' => $fileUrl,
                'file_name' => $filename,
            ], 200);
        }

        // Fallback
        return response()->json(['answer' => '<div style="padding:8px;">I received the Excel data but couldn\'t determine what to do. Please be more specific.</div>'], 200);
    }

    /**
     * Retrieve Excel context from recent chat history.
     * Scans the user's recent messages for uploaded Excel files,
     * reads the most recent one from storage, and returns a text preview.
     */
    private function getExcelContextFromHistory(Request $request): ?string
    {
        $user = $request->user();
        if (!$user)
            return null;

        // Find the most recent user message with a file_path
        $recentExcel = ChatMessage::where('user_id', $user->id)
            ->where('role', 'user')
            ->whereNotNull('file_path')
            ->orderByDesc('id')
            ->first();

        if (!$recentExcel || !$recentExcel->file_path)
            return null;

        $fullPath = Storage::disk('public')->path($recentExcel->file_path);
        if (!file_exists($fullPath))
            return null;

        try {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            if ($ext === 'csv') {
                // Parse CSV
                $handle = fopen($fullPath, 'r');
                if (!$handle)
                    return null;
                $allRows = [];
                while (($row = fgetcsv($handle)) !== false) {
                    $allRows[] = $row;
                }
                fclose($handle);
                if (count($allRows) < 2)
                    return null;
                $headers = $allRows[0];
                $dataRows = array_slice($allRows, 1);
            } else {
                // Parse XLSX/XLS using PhpSpreadsheet (if available) or fallback
                if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $allRows = $sheet->toArray(null, true, true, false);
                    if (count($allRows) < 2)
                        return null;
                    $headers = $allRows[0];
                    $dataRows = array_slice($allRows, 1);
                } else {
                    // If PhpSpreadsheet is not installed, return file info only
                    return "Previously uploaded Excel file: {$recentExcel->file_name} (stored but cannot be re-read without PhpSpreadsheet library)";
                }
            }

            $fileName = $recentExcel->file_name ?? basename($recentExcel->file_path);
            $totalRows = count($dataRows);
            $previewRows = $dataRows;

            $excelText = "=== PREVIOUSLY UPLOADED EXCEL FILE ===\n";
            $excelText .= "File: {$fileName} | Total Rows: {$totalRows}\n";
            $excelText .= "Headers: " . implode(' | ', array_map('strval', $headers)) . "\n\n";

            foreach ($previewRows as $i => $row) {
                $values = array_map(fn($v) => is_null($v) ? '' : (string) $v, $row);
                $excelText .= "Row " . ($i + 1) . ": " . implode(' | ', $values) . "\n";
            }

            return $excelText;
        } catch (\Throwable $e) {
            return "Previously uploaded Excel file: {$recentExcel->file_name} (error reading: {$e->getMessage()})";
        }
    }

    /**
     * Build prompt for Excel data intent classification.
     */
    private function buildExcelPrompt(string $message, string $excelText, string $schema, string $modelMap, array $headers, int $totalRows, string $fkLookup = ''): string
    {
        $headersList = implode(', ', $headers);
        return <<<PROMPT
        === AVAILABLE MODELS ===
        {$modelMap}

        === DATABASE SCHEMA ===
        {$schema}

        {$fkLookup}

        === EXCEL FILE DATA ===
        {$excelText}

        === USER MESSAGE ===
        {$message}

        === TASK ===
        The user has uploaded an Excel file with {$totalRows} rows and these columns: {$headersList}.
        
        Classify the user's intent regarding this Excel data. Return ONLY valid JSON.
        
        INTENTS:
        1. "analyze" — User wants to SUMMARIZE, UNDERSTAND, SEARCH, DISPLAY, SHOW, or GET STATISTICS from the Excel data.
        2. "create" — User wants to INSERT/IMPORT the Excel data into a database table.
        3. "update" — User wants to UPDATE existing records based on Excel data.
        4. "delete" — User wants to DELETE records that match the Excel data.
        
        For "analyze": return {"intent":"analyze"}
        
        For "create": Map Excel columns to database model fields. Return:
        {"intent":"create","actions":[{"model":"ModelName","field_mapping":{"ExcelColumnName":"db_field_name"}}],"pre_create":[],"missing_info":[]}
        
        CRITICAL FIELD_MAPPING RULES FOR CREATE:
        - The keys in field_mapping MUST be the EXACT Excel column header names (case-sensitive, matching the headers above).
        - The values must be the database column names from the model schema.
        - You MUST map ALL relevant Excel columns, not just a subset.
        - Example: if headers are "Name", "Price", "Date" → field_mapping should be {"Name":"full_name","Price":"price","Date":"date"}
        - NEVER truncate or shorten the field_mapping. Map every column that has a corresponding DB field.
        
        For "update": Map Excel columns to model fields. Each record MUST include the "id" field. Return:
        {"intent":"update","actions":[{"model":"ModelName","field_mapping":{"ExcelColumnName":"db_field_name"},"id_column":"ExcelColumnWithId"}],"missing_info":[]}
        
        For "delete": Identify records to delete. Return:
        {"intent":"delete","actions":[{"model":"ModelName","ids":[1,2,3],"description":"Deleting records from Excel"}],"missing_info":[]}
        
        CRITICAL FOREIGN KEY RULES:
        - The FOREIGN KEY LOOKUP TABLE above contains the REAL database IDs for all FK columns.
        - When an Excel column contains NAMES (like "Ahmed", "Ali", "Section A") and the target DB field is a foreign key (_id column), 
          you MUST resolve the name to the real ID from the lookup table.
        - In the field_mapping, map the Excel column to the _id field (e.g., "workforce" → "workforce_id").
        - In the records array (if you generate one), use the REAL integer ID, NOT the name string.
        - If a name from Excel doesn't match any entry in the lookup table, add it to "missing_info".
        
        The field_mapping maps Excel column headers to the corresponding database column names.
        Match columns by semantic similarity (e.g. "Nom" → "full_name", "Prix" → "price", "workforce" → "workforce_id").
        
        Return ONLY the JSON. No text before or after.
        PROMPT;
    }

    /**
     * Execute a validated action plan (create or delete records).
     */
    private function executeActionPlan(array $plan, string $theme = 'dark'): array
    {
        $modelMap = $this->getModelMap();
        $results = [];
        $affectedModels = [];
        $intent = $plan['intent'] ?? 'create';

        try {
            DB::beginTransaction();

            // Track models that used raw SQL (bypass ORM, need manual broadcast)
            $sqlAffectedModels = [];

            // Execute pre_create first (dependencies)
            foreach ($plan['pre_create'] ?? [] as $pc) {
                $modelName = $pc['model'] ?? '';
                if (!isset($modelMap[$modelName]))
                    continue;
                $class = $modelMap[$modelName]['class'];
                $record = $class::withoutEvents(fn() => $class::create($pc['fields'] ?? []));
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
                    if (!empty($action['sql'])) {
                        DB::insert($action['sql']);
                        $created = count($action['records'] ?? []);
                        if ($created === 0)
                            $created = 1;
                        $results[] = "✅ Created {$created} {$modelName} record(s)";
                        $sqlAffectedModels[] = ['model' => $modelName, 'action' => 'created'];
                    } else {
                        $records = $action['records'] ?? [];
                        $created = 0;

                        if (empty($records)) {
                            \Illuminate\Support\Facades\Log::warning("executeActionPlan: No records to create for model={$modelName}. Action data: " . json_encode(array_keys($action)));
                            $results[] = "⚠️ No records to create for {$modelName}";
                        } else {
                            \Illuminate\Support\Facades\Log::info("executeActionPlan: Creating " . count($records) . " records for model={$modelName}. Sample: " . json_encode($records[0] ?? []));
                            $class::withoutEvents(function () use ($class, $records, &$created) {
                                foreach ($records as $rec) {
                                    $class::create($rec);
                                    $created++;
                                }
                            });
                            $results[] = "✅ Created {$created} {$modelName} record(s)";
                        }
                    }
                }

                if ($intent === 'update') {
                    if (!empty($action['sql'])) {
                        $updated = DB::update($action['sql']);
                        $results[] = "✏️ Updated {$updated} {$modelName} record(s)";
                        $sqlAffectedModels[] = ['model' => $modelName, 'action' => 'updated'];
                    } else {
                        $records = $action['records'] ?? [];
                        $updated = 0;
                        $class::withoutEvents(function () use ($class, $records, &$updated) {
                            foreach ($records as $rec) {
                                $id = $rec['id'] ?? null;
                                if (!$id)
                                    continue;
                                $model = $class::find($id);
                                if (!$model)
                                    continue;
                                // Remove protected fields before update
                                $fields = array_diff_key($rec, array_flip(['id', 'created_at', 'updated_at', 'deleted_at']));
                                if (!empty($fields)) {
                                    $model->update($fields);
                                    $updated++;
                                }
                            }
                        });
                        $results[] = "✏️ Updated {$updated} {$modelName} record(s)";
                    }
                }

                if ($intent === 'delete') {
                    if (!empty($action['sql'])) {
                        $deleted = DB::delete($action['sql']);
                        $softLabel = ($action['soft_delete'] ?? false) ? ' (soft-deleted)' : ' (permanently deleted)';
                        $results[] = "🗑️  Deleted {$deleted} {$modelName} record(s){$softLabel}";
                        $sqlAffectedModels[] = ['model' => $modelName, 'action' => 'deleted'];
                    } else {
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
            }

            DB::commit();

            // ---- Broadcast AFTER commit so data is available when frontend refetches ----
            $userName = auth()->user()->name ?? 'AI Assistant';
            $userId = auth()->id();
            $broadcastedModels = [];

            foreach (array_unique($affectedModels) as $affectedModel) {
                try {
                    broadcast(new ModelChanged(
                        $affectedModel,
                        $intent === 'create' ? 'created' : ($intent === 'update' ? 'updated' : 'deleted'),
                        0,
                        $userName,
                        [],
                        $userId
                    ));
                    $broadcastedModels[] = $affectedModel;
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            \Illuminate\Support\Facades\Log::info("AI Assistant: Broadcasted model changes for: " . implode(', ', $broadcastedModels));

            $isDark = ($theme === 'dark');
            $successBg = $isDark ? 'linear-gradient(135deg, rgba(76, 175, 80, 0.18), rgba(27, 94, 32, 0.18))' : 'linear-gradient(135deg,#E8F5E9,#C8E6C9)';
            $successText = $isDark ? '#81C784' : '#2E7D32';
            $successItemColor = $isDark ? '#E2E8F0' : '#333333';
            $html = "<div class='ai-action-success-card' style='background:var(--ai-inner-bg, {$successBg});border-radius:12px;padding:16px;border-left:4px solid #4CAF50;'>";
            $html .= "<div style='font-weight:700;color:{$successText};margin-bottom:8px;'>✅ Action completed successfully</div>";
            $html .= "<ul style='margin:0;padding-left:20px;color:var(--ai-text-color, {$successItemColor});'>";
            foreach ($results as $r) {
                $html .= "<li style='margin:4px 0;'>{$r}</li>";
            }
            $html .= '</ul></div>';
            return ['html' => $html, 'affected_models' => array_unique($affectedModels)];

        } catch (\Exception $e) {
            DB::rollBack();
            $isDark = ($theme === 'dark');
            $errBg = $isDark ? 'linear-gradient(135deg, rgba(239, 83, 80, 0.18), rgba(183, 28, 28, 0.18))' : '#FFEBEE';
            $textColor = $isDark ? '#e2e8f0' : '#333333';
            $html = "<div class='ai-action-error-card' style='background:var(--ai-inner-bg, {$errBg});border-radius:12px;padding:16px;border-left:4px solid #D32F2F;'>"
                . "<strong style='color:#EF5350;'>❌ Error:</strong> <span style='color:var(--ai-text-color, {$textColor});'>" . htmlspecialchars($e->getMessage()) . "</span>"
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

    /**
     * Build a lookup table string for all foreign key columns.
     * For each FK column (ending in _id), load real {id: "display_name"} from the referenced table.
     * This gives the AI and the resolver the data needed to map human names → real IDs.
     */
    private function buildForeignKeyLookupTable(): string
    {
        $modelMap = $this->getModelMap();
        $output = "=== FOREIGN KEY LOOKUP TABLE ===\n";
        $output .= "Use these REAL IDs when mapping Excel values to FK columns.\n\n";
        $processedTables = [];

        foreach ($modelMap as $modelName => $info) {
            if (in_array($modelName, self::PROTECTED_MODELS))
                continue;

            foreach ($info['column_details'] ?? [] as $colName => $detail) {
                // Only FK columns (MUL key = foreign key index)
                if ($detail['key'] !== 'MUL' || !str_ends_with($colName, '_id'))
                    continue;

                // Derive the referenced model name from the column (e.g., workforce_id → Workforce)
                $refName = str_replace('_id', '', $colName);
                $refModelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $refName)));

                if (isset($processedTables[$refModelName]))
                    continue;
                if (!isset($modelMap[$refModelName]))
                    continue;

                $refClass = $modelMap[$refModelName]['class'];
                try {
                    // Try to find the best "name" column for display
                    $refColumns = $modelMap[$refModelName]['columns'];
                    $nameField = null;
                    foreach (['full_name', 'name', 'title', 'label', 'designation'] as $candidate) {
                        if (in_array($candidate, $refColumns)) {
                            $nameField = $candidate;
                            break;
                        }
                    }
                    if (!$nameField)
                        continue;

                    // Load up to 500 records for the lookup
                    $query = $refClass::query();
                    if (in_array('deleted_at', $refColumns)) {
                        $query->whereNull('deleted_at');
                    }
                    $records = $query->select(['id', $nameField])->limit(500)->get();

                    if ($records->isEmpty())
                        continue;

                    $output .= "{$colName} → {$refModelName} (match by '{$nameField}'):\n";
                    foreach ($records as $rec) {
                        $output .= "  ID={$rec->id}: \"{$rec->{$nameField} }\"\n";
                    }
                    $output .= "\n";

                    $processedTables[$refModelName] = [
                        'class' => $refClass,
                        'name_field' => $nameField,
                        'fk_column' => $colName,
                    ];
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $output;
    }

    /**
     * Get the FK lookup as a structured array for programmatic resolution.
     * Returns: ['workforce_id' => ['class' => ..., 'name_field' => 'full_name', 'records' => [{id, name}, ...]]]
     */
    private function getForeignKeyLookupArray(): array
    {
        $modelMap = $this->getModelMap();
        $result = [];
        $processedTables = [];

        foreach ($modelMap as $modelName => $info) {
            if (in_array($modelName, self::PROTECTED_MODELS))
                continue;

            foreach ($info['column_details'] ?? [] as $colName => $detail) {
                if ($detail['key'] !== 'MUL' || !str_ends_with($colName, '_id'))
                    continue;

                $refName = str_replace('_id', '', $colName);
                $refModelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $refName)));

                if (isset($processedTables[$refModelName]))
                    continue;
                if (!isset($modelMap[$refModelName]))
                    continue;

                $refClass = $modelMap[$refModelName]['class'];
                try {
                    $refColumns = $modelMap[$refModelName]['columns'];
                    $nameField = null;
                    foreach (['full_name', 'name', 'title', 'label', 'designation'] as $candidate) {
                        if (in_array($candidate, $refColumns)) {
                            $nameField = $candidate;
                            break;
                        }
                    }
                    if (!$nameField)
                        continue;

                    $query = $refClass::query();
                    if (in_array('deleted_at', $refColumns)) {
                        $query->whereNull('deleted_at');
                    }
                    $records = $query->select(['id', $nameField])->limit(500)->get();

                    $lookup = [];
                    foreach ($records as $rec) {
                        $lookup[] = ['id' => $rec->id, 'name' => $rec->{$nameField}];
                    }

                    $result[$colName] = [
                        'class' => $refClass,
                        'name_field' => $nameField,
                        'model_name' => $refModelName,
                        'records' => $lookup,
                    ];

                    $processedTables[$refModelName] = true;
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * Resolve foreign keys in a set of records.
     * Replaces human-readable names with real database IDs using fuzzy matching.
     *
     * @param array $records Array of records to resolve
     * @param array $fkLookup FK lookup from getForeignKeyLookupArray()
     * @return array ['records' => resolved records, 'unresolved' => fields that couldn't be matched]
     */
    private function resolveForeignKeysInRecords(array $records, array $fkLookup): array
    {
        $resolved = [];
        $unresolved = [];

        foreach ($records as $record) {
            $resolvedRecord = $record;

            foreach ($record as $field => $value) {
                // Skip if the field doesn't end in _id or if the value is already a valid integer
                if (!str_ends_with($field, '_id'))
                    continue;
                if (is_int($value) || (is_numeric($value) && intval($value) == $value))
                    continue;
                if (empty($value))
                    continue;

                // This value is a string in an _id field — needs resolution
                if (!isset($fkLookup[$field]))
                    continue;

                $lookupRecords = $fkLookup[$field]['records'];
                $matched = false;
                $valueStr = trim(strtolower((string) $value));

                // 1. Try exact match
                foreach ($lookupRecords as $lr) {
                    if (strtolower(trim($lr['name'])) === $valueStr) {
                        $resolvedRecord[$field] = $lr['id'];
                        $matched = true;
                        break;
                    }
                }

                // 2. Try contains match
                if (!$matched) {
                    foreach ($lookupRecords as $lr) {
                        $lrName = strtolower(trim($lr['name']));
                        if (str_contains($lrName, $valueStr) || str_contains($valueStr, $lrName)) {
                            $resolvedRecord[$field] = $lr['id'];
                            $matched = true;
                            break;
                        }
                    }
                }

                // 3. Try similar_text (fuzzy) with > 70% similarity
                if (!$matched) {
                    $bestScore = 0;
                    $bestId = null;
                    foreach ($lookupRecords as $lr) {
                        similar_text($valueStr, strtolower(trim($lr['name'])), $pct);
                        if ($pct > $bestScore && $pct >= 70) {
                            $bestScore = $pct;
                            $bestId = $lr['id'];
                        }
                    }
                    if ($bestId !== null) {
                        $resolvedRecord[$field] = $bestId;
                        $matched = true;
                    }
                }

                if (!$matched) {
                    $unresolved[] = "Could not resolve '{$value}' for field '{$field}'";
                }
            }

            $resolved[] = $resolvedRecord;
        }

        return ['records' => $resolved, 'unresolved' => $unresolved];
    }

    // """""""""""""""""""""""""""""""""""""""""
    //  Confirmation & Extraction Helpers
    // """""""""""""""""""""""""""""""""""""""""

    /**
     * Build styled HTML confirmation card for the user.
     */
    private function buildConfirmationHtml(array $plan, string $theme = 'dark'): string
    {
        $intent = $plan['intent'] ?? 'create';
        $isDelete = $intent === 'delete';
        $isUpdate = $intent === 'update';
        $isDark = ($theme === 'dark');

        if ($isDelete) {
            $color = '#EF5350';
            $bgGradient = $isDark ? 'linear-gradient(135deg, rgba(239, 83, 80, 0.18), rgba(183, 28, 28, 0.18))' : 'linear-gradient(135deg,#FFEBEE,#FFCDD2)';
            $icon = '🗑️';
            $title = 'Delete Confirmation';
        } elseif ($isUpdate) {
            $color = '#FFA726';
            $bgGradient = $isDark ? 'linear-gradient(135deg, rgba(255, 167, 38, 0.18), rgba(230, 81, 0, 0.18))' : 'linear-gradient(135deg,#FFF3E0,#FFE0B2)';
            $icon = '✏️';
            $title = 'Update Confirmation';
        } else {
            $color = '#42A5F5';
            $bgGradient = $isDark ? 'linear-gradient(135deg, rgba(66, 165, 245, 0.18), rgba(21, 101, 192, 0.18))' : 'linear-gradient(135deg,#E3F2FD,#BBDEFB)';
            $icon = '📝';
            $title = 'Create Confirmation';
        }

        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#555555';

        $html = "<div class='ai-confirm-card' style='background:var(--ai-inner-bg, {$bgGradient});border-radius:12px;padding:16px;border-left:4px solid {$color};color:var(--ai-text-color, {$textColor});'>";
        $html .= "<div style='font-weight:700;color:{$color};margin-bottom:10px;font-size:14px;'>{$icon} {$title}</div>";

        // Show pre-create items
        if (!empty($plan['pre_create'])) {
            $html .= "<div style='margin-bottom:8px;color:var(--ai-sub-color, {$subColor});font-size:13px;'><strong>Dependencies to create first:</strong></div>";
            $html .= "<ul style='margin:0 0 8px 0;padding-left:20px;font-size:13px;color:var(--ai-text-color, {$textColor});'>";
            foreach ($plan['pre_create'] as $pc) {
                $fields = json_encode($pc['fields'] ?? [], JSON_UNESCAPED_UNICODE);
                $html .= "<li><strong>{$pc['model']}</strong>: {$fields}</li>";
            }
            $html .= "</ul>";
        }

        // Show main actions
        $html .= "<div style='margin-bottom:8px;color:var(--ai-sub-color, {$subColor});font-size:13px;'><strong>Actions:</strong></div>";
        $html .= "<ul style='margin:0 0 12px 0;padding-left:20px;font-size:13px;color:var(--ai-text-color, {$textColor});'>";
        $grandTotal = 0;
        foreach ($plan['actions'] as $action) {
            $model = $action['model'] ?? '?';
            if ($intent === 'create') {
                $count = count($action['records'] ?? []);
                $grandTotal += $count;
                $html .= "<li>Create <strong>{$count}</strong> {$model} record(s)</li>";
            } elseif ($intent === 'update') {
                $count = count($action['records'] ?? []);
                // Use estimated_count for SQL-based updates
                if ($count === 0 && isset($action['estimated_count'])) {
                    $count = $action['estimated_count'];
                }
                $grandTotal += $count;
                $desc = $action['description'] ?? '';
                $html .= "<li>Update <strong>{$count}</strong> {$model} record(s)" . ($desc ? " — {$desc}" : '') . "</li>";
            } else {
                $count = count($action['ids'] ?? []);
                $grandTotal += $count;
                $softLabel = ($action['soft_delete'] ?? false) ? '(soft-delete, recoverable)' : '<span style="color:#EF5350;font-weight:700;">(⚠️ permanent!)</span>';
                $desc = $action['description'] ?? '';
                $html .= "<li>Delete <strong>{$count}</strong> {$model} record(s) {$softLabel}" . ($desc ? "  {$desc}" : '') . "</li>";
            }
        }
        $html .= "</ul>";

        // Show total records summary
        if ($grandTotal > 0) {
            $actionLabel = $isDelete ? 'deleted' : ($isUpdate ? 'updated' : 'created');
            $html .= "<div style='font-size:13px;color:{$color};font-weight:600;margin-bottom:8px;'>📊 Total: <strong>{$grandTotal}</strong> record(s) will be {$actionLabel}</div>";
        }

        // Show errors
        if (!empty($plan['errors'])) {
            $html .= "<div style='color:#FFA726;font-size:12px;margin-bottom:8px;'>⚠️ Warnings: " . implode(', ', $plan['errors']) . "</div>";
        }

        $html .= "<div style='font-size:13px;color:var(--ai-text-color, {$textColor});'>Reply <strong>\"yes\"</strong> to confirm or <strong>\"no\"</strong> to cancel.</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Build cancel confirmation HTML.
     */
    private function buildCancelHtml(string $theme = 'dark'): string
    {
        $isDark = ($theme === 'dark');
        $bg = $isDark ? '#1e2634' : '#ECEFF1';
        $textColor = $isDark ? '#e2e8f0' : '#455A64';
        return "<div class=\"ai-cancel-card\" style=\"background:var(--ai-inner-bg, {$bg});border-radius:12px;padding:14px;border-left:4px solid #607D8B;\">"
            . "<strong style=\"color:#78909C;\">🚫 Action cancelled.</strong> <span style=\"color:var(--ai-text-color, {$textColor});\">No changes were made.</span>"
            . '</div>';
    }

    /**
     * Build a styled HTML table displaying ALL Excel data without truncation.
     * This is used when the user asks to "display" or "show" data — bypasses AI to avoid truncation.
     */
    private function buildExcelDisplayHtml(array $headers, array $rows, string $filename, int $totalRows, string $theme = 'dark'): string
    {
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1e2634' : 'linear-gradient(135deg,#E3F2FD,#BBDEFB)';
        $tableBg = $isDark ? '#141c28' : '#ffffff';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#555555';
        $borderColor = $isDark ? 'rgba(255, 255, 255, 0.08)' : '#E0E0E0';

        $html = "<div class='ai-excel-display-card' style='background:var(--ai-inner-bg, {$cardBg});border-radius:12px;padding:16px;border-left:4px solid #1976D2;margin-bottom:8px;'>";
        $html .= '<div style="font-weight:700;color:#42A5F5;margin-bottom:8px;font-size:14px;">📊 ' . htmlspecialchars($filename) . '</div>';
        $html .= "<div style='color:var(--ai-sub-color, {$subColor});font-size:13px;margin-bottom:12px;'>Total rows: <strong style='color:var(--ai-text-color, {$textColor});'>" . $totalRows . '</strong></div>';
        $html .= '<div style="overflow-x:auto;max-height:600px;overflow-y:auto;">';
        $html .= "<table class='ai-analytics-table' style='width:100%;border-collapse:collapse;font-size:12px;background:{$tableBg};border-radius:8px;overflow:hidden;'>";

        // Header row
        $html .= '<thead><tr>';
        $html .= '<th style="background:#1565C0;color:#fff;padding:8px 10px;text-align:center;font-weight:600;border:1px solid #1976D2;position:sticky;top:0;z-index:1;">#</th>';
        foreach ($headers as $header) {
            $html .= '<th style="background:#1565C0;color:#fff;padding:8px 10px;text-align:left;font-weight:600;border:1px solid #1976D2;white-space:nowrap;position:sticky;top:0;z-index:1;">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';

        // Data rows — ALL of them, no truncation
        $html .= '<tbody>';
        foreach ($rows as $i => $row) {
            $bgColor = ($i % 2 === 0) ? ($isDark ? '#141c28' : '#fff') : ($isDark ? 'rgba(255, 255, 255, 0.03)' : '#F5F5F5');
            $html .= "<tr style='background:{$bgColor};'>";
            $html .= "<td style='padding:6px 10px;border:1px solid {$borderColor};text-align:center;color:var(--ai-sub-color, {$subColor});font-weight:500;'>" . ($i + 1) . '</td>';
            foreach ($headers as $colIdx => $header) {
                $value = $row[$colIdx] ?? '';
                $displayValue = is_null($value) ? '' : htmlspecialchars((string) $value);
                // Right-align if numeric
                $align = is_numeric($value) ? 'text-align:right;' : 'text-align:left;';
                $html .= "<td style='padding:6px 10px;border:1px solid {$borderColor};{$align}color:var(--ai-text-color, {$textColor});'>" . $displayValue . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Extract pending action from conversation messages.
     */
    private function extractPendingAction(string $messagesJson): ?array
    {
        $messages = json_decode($messagesJson, true);
        if (!is_array($messages)) {
            \Illuminate\Support\Facades\Log::error('extractPendingAction: messagesJson is not a valid JSON array');
            return null;
        }

        // Search backwards for the most recent pending action
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $msg = $messages[$i];
            $text = $msg['text'] ?? '';

            if (str_contains($text, 'data-action=')) {
                \Illuminate\Support\Facades\Log::info("extractPendingAction found data-action in msg $i. Length: " . strlen($text));

                // 1. Try base64 encoded format (safest, no quote issues)
                if (preg_match('/data-action=["\']?([a-zA-Z0-9+\/]+={0,2})["\']?/', $text, $m)) {
                    \Illuminate\Support\Facades\Log::info("extractPendingAction base64 regex matched. Match length: " . strlen($m[1]));
                    $decoded = json_decode(base64_decode($m[1]), true);
                    if (is_array($decoded) && isset($decoded['intent'])) {
                        \Illuminate\Support\Facades\Log::info("extractPendingAction base64 decoded successfully. Intent: " . $decoded['intent']);
                        return $decoded;
                    } else {
                        \Illuminate\Support\Facades\Log::error("extractPendingAction base64 decode failed or missing intent. Decoded: " . print_r($decoded, true));
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info("extractPendingAction base64 regex DID NOT match.");
                }

                // 2. Fallback for older messages using HTML entities (might fail on unescaped quotes)
                if (preg_match('/data-action=(["\'])(.*?)\1/s', $text, $m)) {
                    $raw = $m[2];
                    \Illuminate\Support\Facades\Log::info("extractPendingAction fallback regex matched. Raw length: " . strlen($raw));
                    // Try decoding HTML entities
                    $decoded = json_decode(htmlspecialchars_decode($raw, ENT_QUOTES), true);

                    // Sometimes frontend innerHTML fully unescapes things
                    if (!is_array($decoded) || !isset($decoded['intent'])) {
                        $decoded = json_decode($raw, true);
                    }

                    if (is_array($decoded) && isset($decoded['intent'])) {
                        \Illuminate\Support\Facades\Log::info("extractPendingAction fallback decoded successfully. Intent: " . $decoded['intent']);
                        return $decoded;
                    } else {
                        \Illuminate\Support\Facades\Log::error("extractPendingAction fallback decode failed. Decoded: " . print_r($decoded, true));
                    }
                }
            }
        }

        \Illuminate\Support\Facades\Log::info("extractPendingAction found no pending action in " . count($messages) . " messages.");
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
        3. "update"  The user wants to UPDATE, MODIFY, EDIT, or CHANGE existing records. Example: "update workforce price", "change section name", "modify supplier"
        4. "delete"  The user wants to DELETE, REMOVE, or SUPPRESS records. Example: "delete supplier 5", "remove this ship"
        5. "confirm"  The user is confirming a previous action proposal by saying: yes, ok, oui, confirm, go ahead, d'accord, proceed
        6. "cancel"  The user is cancelling by saying: no, cancel, stop, annuler

        CRITICAL: If the user uses words like "create", "add", "insert", "entry", "register", "new", "ajouter", "créer", "enregistrer"  the intent is ALWAYS "create", NEVER "read".
        CRITICAL: If the user uses words like "update", "modify", "edit", "change", "modifier", "mettre à jour", "changer"  the intent is ALWAYS "update", NEVER "read".
        CRITICAL: If the user uses words like "delete", "remove", "supprimer"  the intent is ALWAYS "delete", NEVER "read".
        CRITICAL: There is NO limit on the number of records. If the user asks to create 50, 100, 500 or more records, generate ALL of them in the "records" array. Never truncate or limit the output.

        CRITICAL TABLE STUDY RULES (for CREATE/UPDATE intent):
        Before generating any records, you MUST carefully study the AVAILABLE MODELS section above AND the DATABASE SCHEMA. Pay attention to:
        1. **Column types**: Use the correct data type for each field (string, integer, decimal, date, enum, etc.)
        2. **Required vs nullable**: If a column is "required", you MUST provide a value. If "nullable", you MAY omit it.
        3. **Enum columns**: Only use the allowed values listed in the type (e.g. enum('Q1','Q2') means only 'Q1' or 'Q2' are valid).
        4. **Foreign keys [FK]**: These columns reference another table's ID. Use valid IDs if known. If you only know the text/name, you MUST use a SQL subquery in your SQL string. Example: (SELECT `id` FROM `workforces` WHERE `full_name` = 'Ahmed Alami')
        5. **Default values**: If a column has a default, you can omit it and the database will use the default.
        6. **Only use fillable fields**: Only include fields listed in the "fillable" attribute. Never include id, created_at, updated_at, or deleted_at in the 'records' array (but DO include them in the 'sql' query like NOW()).
        7. **Decimal fields**: Use numeric values (not strings) for decimal/numeric columns.
        8. **Date fields**: Use 'YYYY-MM-DD' format for date columns.
        9. **JSON/Array columns (CRITICAL)**: Check the schema "casts" and "LaravelType" fields. If a column has LaravelType "json_array" or cast type "array"/"json", it stores a JSON array. 
           - In SQL: wrap the value as a JSON string like '["value1","value2"]' or '["26-00011"]'. NEVER pass a plain string like '26-00011'.
           - In "records" array: pass an actual array like ["value1","value2"] or ["26-00011"]. NEVER pass a plain string.
           - Example: reception_tracking_number is a JSON array column. Correct SQL: SET `reception_tracking_number` = '["26-00011"]'. Correct record: {"reception_tracking_number": ["26-00011"]}
        10. **Boolean columns**: Use true/false in records, and 1/0 in SQL.
        If you are unsure about a required field value, add it to "missing_info" array instead of guessing.

        CRITICAL SQL RULE: You MUST provide a raw SQL query in the "sql" field for any create, update, or delete action. The query must use the actual database table name.

        For CREATE: use exact model names from the AVAILABLE MODELS list. Put ALL field values in "records" array AND provide the "sql" field with an INSERT statement. Example:
        {"intent":"create","actions":[{"model":"Section","sql":"INSERT INTO `sections` (`name`, `daily_price`, `created_at`, `updated_at`) VALUES ('test', 100, NOW(), NOW())","records":[{"name":"test","daily_price":100}]}],"pre_create":[],"missing_info":[]}

        For bulk CREATE (e.g. "add 30 workforces"): generate all 30 records in the "records" array with realistic data, and write a single bulk INSERT SQL query in the "sql" field (e.g. VALUES (...), (...), ...).

        For UPDATE: provide the "sql" field with an UPDATE statement. Also include "records" for validation. Example:
        {"intent":"update","actions":[{"model":"Section","sql":"UPDATE `sections` SET `name` = 'new name', `daily_price` = 200, `updated_at` = NOW() WHERE `id` = 5","records":[{"id":5,"name":"new name","daily_price":200}]}],"missing_info":[]}

        For DELETE: provide the "sql" field with a DELETE statement (or UPDATE for soft-delete). Provide "ids" array. Example:
        {"intent":"delete","actions":[{"model":"Supplier","sql":"DELETE FROM `suppliers` WHERE `id` = 5","ids":[5],"description":"Supplier #5"}]}

        Return ONLY the JSON. No text before or after.
        PROMPT;
    }

    /**
     * Persist user and agent messages to the database.
     */
    private function persistMessages(Request $request, string $question, string $answer, ?array $reasoning = null): void
    {
        $user = $request->user();
        if ($user) {
            ChatMessage::withoutEvents(function () use ($user, $question, $answer, $reasoning) {
                ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
                ChatMessage::create([
                    'user_id' => $user->id,
                    'role' => 'agent',
                    'text' => $answer,
                    'reasoning' => $reasoning ? json_encode($reasoning) : null,
                ]);
            });
        }
    }

    /**
     * Build rich, transparent reasoning payload matching modern AI assistant thought structures.
     */
    private function buildReasoningPayload(string $question, string $intent, ?string $sqlQuery, array $rows, float $thinkingDuration): array
    {
        $hasSql = !empty($sqlQuery);
        $rowCount = count($rows);

        $steps = [
            [
                'phase' => 'intent',
                'title' => 'Business Context & Intent Analysis',
                'detail' => "Identified analytical intent as [{$intent}]. Target scope: Enterprise ERP business data streams.",
                'is_sql' => false,
            ],
            [
                'phase' => 'schema_sql',
                'title' => 'Data Model & Query Formulation',
                'detail' => $hasSql ? $sqlQuery : 'Applied certified ERP analytical rules and business heuristics directly.',
                'is_sql' => $hasSql,
            ],
            [
                'phase' => 'execution',
                'title' => 'Enterprise Verification & Metric Validation',
                'detail' => $hasSql ? "Retrieved and verified {$rowCount} certified record(s) across enterprise streams." : "Validated in-memory ERP intelligence dataset.",
                'is_sql' => false,
            ],
            [
                'phase' => 'synthesis',
                'title' => 'Executive Presentation & Visual Synthesis',
                'detail' => 'Structured executive KPI cards, verified metrics against enterprise records, and prepared interactive visualizations.',
                'is_sql' => false,
            ],
        ];

        return [
            'thinking_time' => $thinkingDuration,
            'model' => $this->ai->getModel(),
            'intent' => $intent,
            'sql_query' => $sqlQuery,
            'rows_count' => $rowCount,
            'steps' => $steps,
        ];
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
        8. LIMIT results to 5000 rows max unless the user asks for all or specifically requests to see all data.
        9. For aggregations (SUM, COUNT, AVG), always include meaningful GROUP BY and aliases.
        10. The currency is MAD (Moroccan Dirham).
        11. Weight is measured in KG (kilograms).
        12. "charges" = purchases/incoming fish. "sells" = outgoing sales to clients.
        13. warehouse_stockable_tracking_number_operations is a polymorphic table (sourceable_type + sourceable_id) linking to both charges and sells. Contains unit_price, total_price, total_weight, total_package_count.
        14. non_warehouse_stockable_operations: For items that don't go through warehouse (direct charges like services).
        15. Fish types are in "fish_types" table, fish families in "fish_families" table.
        16. don't get users conversation history.
        17. **JSON columns**: Check the schema for columns with LaravelType "json_array" or cast "array". These store JSON arrays (e.g. reception_tracking_number, tracking_onp_numbers). When querying these columns:
            - To search inside: use JSON_CONTAINS() or JSON_SEARCH(). Example: JSON_CONTAINS(reception_tracking_number, '"26-00011"')
            - To extract values: use JSON_UNQUOTE(JSON_EXTRACT(column, '$[0]'))
            - When displaying: use JSON_UNQUOTE() to remove quotes from the output

        === CONVERSATION HISTORY ===
        {$messages}

        === USER QUESTION ===
        {$question}
        PROMPT;
    }

    /**
     * Resolve module design tokens according to claude.md and BluePrintFish design system.
     */
    private function resolveModuleDesign(string $question, ?string $sql = null, string $lang = 'en'): array
    {
        $text = mb_strtolower($question . ' ' . ($sql ?? ''), 'UTF-8');

        // 1. Charge / Purchase Module
        if (preg_match('/(charge|achat|reception|supplier|fournisseur|achat_frais|rsw|glace|emballage)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Purchases & Charges Module',
                'es' => 'Módulo de Compras y Cargas',
                'ar' => 'وحدة المشتريات والشحنات',
                default => 'Module Achats & Charges',
            };
            $routeLbl = match ($lang) {
                'en' => 'Purchases Module',
                'es' => 'Módulo Compras',
                'ar' => 'وحدة المشتريات',
                default => 'Module Achats',
            };
            return [
                'name' => $name,
                'color' => '#42A5F5',
                'gradient' => 'linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)',
                'icon' => 'mdi-truck-delivery',
                'route' => '/charges',
                'route_label' => $routeLbl,
            ];
        }

        // 2. Shipping / Sales Module
        if (preg_match('/(shipping|vente|expedition|client|sale|facture_client|glace_client|emballage_client)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Shippings & Sales Module',
                'es' => 'Módulo de Envíos y Ventas',
                'ar' => 'وحدة المبيعات والشحنات',
                default => 'Module Expéditions & Ventes',
            };
            $routeLbl = match ($lang) {
                'en' => 'Sales Module',
                'es' => 'Módulo Ventas',
                'ar' => 'وحدة المبيعات',
                default => 'Module Ventes',
            };
            return [
                'name' => $name,
                'color' => '#4643A0',
                'gradient' => 'linear-gradient(135deg, #372E7D 0%, #4643A0 40%, #6C66BB 100%)',
                'icon' => 'mdi-ship-wheel',
                'route' => '/shippings',
                'route_label' => $routeLbl,
            ];
        }

        // 3. Production / Factory Module
        if (preg_match('/(production|usine|rendement|congel|yield|farine|guano)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Production & Factory Module',
                'es' => 'Módulo de Producción y Fábrica',
                'ar' => 'وحدة الإنتاج والمصنع',
                default => 'Module Production & Usine',
            };
            $routeLbl = match ($lang) {
                'en' => 'Production Module',
                'es' => 'Módulo Producción',
                'ar' => 'وحدة الإنتاج',
                default => 'Module Production',
            };
            return [
                'name' => $name,
                'color' => '#00897B',
                'gradient' => 'linear-gradient(135deg, #00695C 0%, #00897B 40%, #26A69A 100%)',
                'icon' => 'mdi-factory',
                'route' => '/production',
                'route_label' => $routeLbl,
            ];
        }

        // 4. Workforce / Fortnight Module
        if (preg_match('/(fortnight|quinzaine|ouvrier|workforce|section|corporal|caporal|pointage)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Fortnights & Workforce Module',
                'es' => 'Módulo de Quincenas y Personal',
                'ar' => 'وحدة نصف الشهرية واليد العاملة',
                default => 'Module Quinzaines & Main-d’œuvre',
            };
            $routeLbl = match ($lang) {
                'en' => 'Fortnights Module',
                'es' => 'Módulo Quincenas',
                'ar' => 'وحدة نصف الشهرية',
                default => 'Module Quinzaines',
            };
            return [
                'name' => $name,
                'color' => '#1565C0',
                'gradient' => 'linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)',
                'icon' => 'mdi-account-group',
                'route' => '/workforce/fortnight',
                'route_label' => $routeLbl,
            ];
        }

        // 5. Warehouse / Stock Module
        if (preg_match('/(warehouse|entrepot|magasin|stock|inventaire|balance)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Warehouses & Stock Module',
                'es' => 'Módulo de Almacenes y Stocks',
                'ar' => 'وحدة المستودعات والمخزون',
                default => 'Module Entrepôts & Stocks',
            };
            $routeLbl = match ($lang) {
                'en' => 'Warehouses Module',
                'es' => 'Módulo Almacenes',
                'ar' => 'وحدة المستودعات',
                default => 'Module Entrepôts',
            };
            return [
                'name' => $name,
                'color' => '#a21ee5',
                'gradient' => 'linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%)',
                'icon' => 'mdi-warehouse',
                'route' => '/warehouses',
                'route_label' => $routeLbl,
            ];
        }

        // 6. Supplier Invoice / Settlement Module
        if (preg_match('/(supplier_invoice|fournisseur.*facture|facture.*fournisseur|reglement.*fournisseur)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Supplier Invoices & Payments',
                'es' => 'Facturas y Pagos de Proveedores',
                'ar' => 'فواتير وتسويات الموردين',
                default => 'Factures & Règlements Fournisseurs',
            };
            $routeLbl = match ($lang) {
                'en' => 'Supplier Invoices',
                'es' => 'Facturas Proveedores',
                'ar' => 'فواتير الموردين',
                default => 'Factures Fournisseurs',
            };
            return [
                'name' => $name,
                'color' => '#E65100',
                'gradient' => 'linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)',
                'icon' => 'mdi-receipt-text-outline',
                'route' => '/supplier_invoice/list_supplier_invoice',
                'route_label' => $routeLbl,
            ];
        }

        // 7. Client Invoice / Settlement Module
        if (preg_match('/(client_invoice|client.*facture|facture.*client|reglement.*client)/ui', $text)) {
            $name = match ($lang) {
                'en' => 'Client Invoices & Settlements',
                'es' => 'Facturas y Cobros de Clientes',
                'ar' => 'فواتير ومستحقات العملاء',
                default => 'Factures & Règlements Clients',
            };
            $routeLbl = match ($lang) {
                'en' => 'Client Invoices',
                'es' => 'Facturas Clientes',
                'ar' => 'فواتير العملاء',
                default => 'Factures Clients',
            };
            return [
                'name' => $name,
                'color' => '#00838F',
                'gradient' => 'linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)',
                'icon' => 'mdi-file-document-outline',
                'route' => '/client_invoice/list_client_invoice',
                'route_label' => $routeLbl,
            ];
        }

        $defaultName = match ($lang) {
            'en' => 'Jupiter ERP Intelligence',
            'es' => 'Inteligencia ERP Jupiter',
            'ar' => 'ذكاء تخطيط موارد المؤسسات جوبيتر',
            default => 'Jupiter ERP Intelligence',
        };
        $defaultRouteLbl = match ($lang) {
            'en' => 'Main Dashboard',
            'es' => 'Panel Principal',
            'ar' => 'لوحة التحكم الرئيسية',
            default => 'Tableau de Bord',
        };

        return [
            'name' => $defaultName,
            'color' => '#1565C0',
            'gradient' => 'linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)',
            'icon' => 'mdi-robot-happy-outline',
            'route' => '/main-dashboard',
            'route_label' => $defaultRouteLbl,
        ];
    }

    /**
     * Build the answer/analysis prompt from the retrieved data.
     */
    private function buildAnswerPrompt(string $question, string $messages, string $schema, string $comments, ?string $result, ?string $sqlError, string $businessCtx, string $routes, ?string $excelContext = null, string $theme = 'dark', ?string $sqlQuery = null, string $detectedLang = 'en'): string
    {
        $id = uniqid('ai_');
        $dataSection = '';
        if ($sqlError) {
            $dataSection = "SQL Error occurred: {$sqlError}. Inform the user politely and suggest they rephrase.";
        } elseif ($result === null || $result === 'null' || $result === '[]') {
            $dataSection = "The database query returned NO results. Tell the user there is no data matching their question.";
        } else {
            $dataSection = "Database query results (use this as the SINGLE source of truth): {$result}";
        }

        $excelSection = '';
        if ($excelContext) {
            $excelSection = "\n=== UPLOADED EXCEL FILE DATA ===\nThe user has previously uploaded an Excel file. If their question relates to this file, use this data to answer:\n{$excelContext}\n";
        }

        $mod = $this->resolveModuleDesign($question, $sqlQuery ?? '', $detectedLang);
        $modName = $mod['name'];
        $modCol = $mod['color'];
        $modGrad = $mod['gradient'];
        $modIco = $mod['icon'];
        $modRoute = $mod['route'];
        $modRouteLbl = $mod['route_label'];

        $badgeText = match ($detectedLang) {
            'en' => 'Validated Analysis • AI Core',
            'es' => 'Análisis Validado • Núcleo IA',
            'ar' => 'تحليل معتمد • النواة الذكية',
            default => 'Analyse Validée • IA Core',
        };
        $kpiAmountLabel = match ($detectedLang) {
            'en' => 'TOTAL AMOUNT',
            'es' => 'MONTO TOTAL',
            'ar' => 'المبلغ الإجمالي',
            default => 'TOTAL MONTANT',
        };
        $kpiWeightLabel = match ($detectedLang) {
            'en' => 'TOTAL WEIGHT or TOP ITEM',
            'es' => 'PESO TOTAL o ELEMENTO TOP',
            'ar' => 'الوزن الإجمالي أو العنصر الأبرز',
            default => 'POIDS TOTAL ou ÉLÉMENT TOP',
        };
        $tableTitleLabel = match ($detectedLang) {
            'en' => 'RECORD DETAILS & METRICS',
            'es' => 'DETALLES DE REGISTROS Y MÉTRICAS',
            'ar' => 'تفاصيل السجلات والمؤشرات',
            default => 'DÉTAILS DES ENREGISTREMENTS',
        };
        $chartTitleLabel = match ($detectedLang) {
            'en' => 'Graphical Visualization',
            'es' => 'Visualización Gráfica',
            'ar' => 'التمثيل البياني',
            default => 'Visualisation Graphique',
        };
        $footerLabel = match ($detectedLang) {
            'en' => 'Certified ERP Data • Single Source of Truth',
            'es' => 'Datos Certificados ERP • Fuente Única de Verdad',
            'ar' => 'بيانات تخطيط موارد المؤسسات المعتمدة • مصدر الحقيقة الموثوق',
            default => 'Données Certifiées ERP • Source Unique de Vérité',
        };
        $directAnswerPlaceholder = match ($detectedLang) {
            'en' => '[Write a direct, crystal-clear 1-2 sentence executive answer in English using actual totals from the database data]',
            'es' => '[Escribe una respuesta ejecutiva directa y clara de 1-2 frases en español usando los totales reales de los datos]',
            'ar' => '[اكتب إجابة مباشرة ودقيقة في جملة أو جملتين باللغة العربية باستخدام الأرقام الحقيقية]',
            default => '[Write a direct, crystal-clear 1-2 sentence executive answer in French using actual totals from the database data]',
        };

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';
        $borderCol = $isDark ? 'rgba(66, 165, 245, 0.25)' : 'rgba(0, 0, 0, 0.08)';
        $innerBorder = $isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';
        $thBg = $isDark ? 'rgba(255, 255, 255, 0.06)' : '#f1f5f9';
        $thBorder = $isDark ? 'rgba(255, 255, 255, 0.08)' : '#e2e8f0';
        $safeQuestion = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');

        return <<<PROMPT
=== YOUR ROLE & IDENTITY ===
You are "Jupiter AI", the Elite Enterprise AI Data Analyst and UI Architect for the Jupiter ERP fish management system.
Always deliver certified, high-precision ERP enterprise intelligence.
You transform raw database results into visually stunning, responsive, highly polished dashboards using clean HTML5, Material Design Icons (mdi), and interactive ApexCharts.

=== TARGET LANGUAGE: {$detectedLang} ===
CRITICAL MANDATORY INSTRUCTION: The user asked in "{$detectedLang}" (en = English, es = Spanish, fr = French, ar = Arabic).
Every label, header, metric card title, table header, button, chart label, and tooltip MUST be in {$detectedLang}!
- If {$detectedLang} is "en": Everything MUST be in English. NEVER use French words like "Réponse Directe", "Détails", "Données Certifiées".
- If {$detectedLang} is "es": Everything MUST be in Spanish.
- If {$detectedLang} is "fr": Everything MUST be in French.
- If {$detectedLang} is "ar": Everything MUST be in Arabic.

=== USER QUESTION ===
{$question}

=== DATABASE DATA (ABSOLUTE SOURCE OF TRUTH - NEVER INVENT NUMBERS) ===
{$dataSection}
{$excelSection}

=== MODULE DESIGN SYSTEM TOKENS (MANDATORY ACCORDING TO CLAUDE.MD) ===
- Module Name: {$modName}
- Primary Accent Color: {$modCol}
- Hero Header Gradient: {$modGrad}
- Module Icon: {$modIco}
- Recommended Shortcut Route: {$modRoute} ({$modRouteLbl})
- Active System Theme: "{$theme}"
  Card Background: "{$cardBg}"
  Inner Background: "{$innerBg}"
  Text Color: "{$textColor}"
  Subtext / Secondary Color: "{$subColor}"
  Border Color: "{$borderCol}"
  Inner Border: "{$innerBorder}"
  Table Header Background: "{$thBg}"

=== APPLICATION ROUTES & NAVIGATION RULES (MANDATORY ACCORDING TO ROUTES.JS) ===
{$routes}

=== MANDATORY ARCHITECTURAL RULES (ZERO COMPROMISE ON UI/UX) ===
1. OUTPUT PATTERN:
   Output ONLY the raw HTML container block followed by the <script> block.
   NO markdown code fences (NO ```html), NO conversational preamble.
   Start directly with: <div id="ai_{$id}" class="ai-analytics-card" style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid {$borderCol}; box-shadow: 0 10px 30px rgba(0,0,0,0.06); width: 100%; box-sizing: border-box; font-family: inherit;">
   End with: </script>

2. CRITICAL MDI ICONS SYNTAX:
   Every icon tag MUST include BOTH the base "mdi" class AND the specific icon name:
   CORRECT: <i class="v-icon notranslate mdi mdi-calendar-clock" style="color: {$modCol};"></i>
   CORRECT: <i class="v-icon notranslate mdi mdi-cash-multiple" style="color: #00E676;"></i>
   CORRECT: <i class="v-icon notranslate mdi mdi-chart-bell-curve-cumulative" style="color: {$modCol};"></i>
   WRONG: <i class="mdi-calendar"></i> (missing "mdi" class, renders as an empty broken square box [])!
   NEVER write an icon class without the leading "mdi " class!

3. NUMBER FORMATTING RULES:
   - Currency: 2 decimals with MAD (e.g., 3 433 830.00 MAD, 147 769 289.55 MAD).
   - Weights: 2 decimals with KG (e.g., 1 500.00 KG, 850.50 KG).
   - Counts & Workers: Integers with thousand space separator, NO decimals (e.g., 21 092 j, 1 250 ouvriers, 6 categories).
   - Percentages: 1 decimal with % (e.g., 48.7%).

4. MANDATORY 6-SECTION DASHBOARD STRUCTURE:
   Follow this EXACT HTML layout skeleton:

<div id="ai_{$id}" class="ai-analytics-card" style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid {$borderCol}; box-shadow: 0 10px 30px rgba(0,0,0,0.06); width: 100%; box-sizing: border-box; font-family: inherit;">

  <!-- SECTION 1: HERO HEADER BANNER -->
  <div style="background: {$modGrad}; border-radius: 16px; padding: 18px 22px; margin-bottom: 20px; color: white; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 6px 20px {$modCol}33; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 14px;">
      <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); flex-shrink: 0;">
        <i class="v-icon notranslate mdi {$modIco}" style="font-size: 26px; color: white;"></i>
      </div>
      <div>
        <div style="font-size: 16px; font-weight: 800; letter-spacing: 0.3px;">{$modName}</div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 2px;">{$safeQuestion}</div>
      </div>
    </div>
    <div style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; backdrop-filter: blur(8px); display: flex; align-items: center; gap: 6px;">
      <i class="v-icon notranslate mdi mdi-check-decagram" style="font-size: 13px;"></i>
      {$badgeText}
    </div>
  </div>

  <!-- SECTION 2: DIRECT SYNTHESIS HIGHLIGHT BANNER -->
  <div style="background: {$innerBg}; border-left: 4px solid {$modCol}; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 13.5px; line-height: 1.6; color: {$textColor}; border: 1px solid {$innerBorder};">
    <div style="display: flex; align-items: flex-start; gap: 10px;">
      <i class="v-icon notranslate mdi mdi-information-outline" style="font-size: 18px; color: {$modCol}; margin-top: 2px; flex-shrink: 0;"></i>
      <div>{$directAnswerPlaceholder}</div>
    </div>
  </div>

  <!-- SECTION 3: RESPONSIVE KPI METRIC CARDS (CSS Grid: 2 to 4 cards) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="ai-analytics-kpi" style="background: {$innerBg}; border-radius: 14px; padding: 16px 18px; border: 1px solid {$innerBorder}; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: {$modCol}18; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="v-icon notranslate mdi mdi-cash-multiple" style="font-size: 22px; color: {$modCol};"></i>
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 700; color: {$subColor}; text-transform: uppercase; letter-spacing: 0.5px;">{$kpiAmountLabel}</div>
        <div style="font-size: 20px; font-weight: 800; color: {$modCol}; margin-top: 2px;">[Formatted Value e.g. 147 769 289.55 MAD]</div>
      </div>
    </div>
    <div class="ai-analytics-kpi" style="background: {$innerBg}; border-radius: 14px; padding: 16px 18px; border: 1px solid {$innerBorder}; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: #00897B18; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="v-icon notranslate mdi mdi-scale" style="font-size: 22px; color: #00897B;"></i>
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 700; color: {$subColor}; text-transform: uppercase; letter-spacing: 0.5px;">{$kpiWeightLabel}</div>
        <div style="font-size: 20px; font-weight: 800; color: #00897B; margin-top: 2px;">[Formatted Value]</div>
      </div>
    </div>
  </div>

  <!-- SECTION 4: DETAILED DATA TABLE (MANDATORY: MUST USE <table>, <thead>, <tbody>, <tr>, <th>, <td>) -->
  <div class="ai-analytics-inner" style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid {$innerBorder};">
    <div style="font-size: 13px; font-weight: 800; color: {$modCol}; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <i class="v-icon notranslate mdi mdi-table" style="font-size: 18px;"></i>
      {$tableTitleLabel}
    </div>
    <div style="overflow-x: auto; width: 100%;">
      <table class="ai-analytics-table" style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; border-radius: 12px; overflow: hidden; border: 1px solid {$innerBorder};">
        <thead>
          <tr style="background: {$thBg}; color: {$subColor}; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700;">
            <th style="padding: 12px 16px; text-align: left; border-bottom: 2px solid {$thBorder};">[Name / Category / Month]</th>
            <th style="padding: 12px 16px; text-align: right; border-bottom: 2px solid {$thBorder};">[Weight KG / Count]</th>
            <th style="padding: 12px 16px; text-align: right; border-bottom: 2px solid {$thBorder};">[Total MAD]</th>
          </tr>
        </thead>
        <tbody>
          <!-- MANDATORY: Loop every row in the database data into a <tr>! NEVER mash rows into a plain text div! -->
          <tr style="border-bottom: 1px solid {$innerBorder};">
            <td style="padding: 12px 16px; text-align: left; font-weight: 600;">[Item Name]</td>
            <td style="padding: 12px 16px; text-align: right;">[Weight or Count]</td>
            <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: {$modCol};">[Price MAD]</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- SECTION 5: INTERACTIVE APEXCHART (ALWAYS RENDER A CLEAN CHART) -->
  <div class="ai-analytics-inner" style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid {$innerBorder};">
    <div style="font-size: 13px; font-weight: 800; color: {$modCol}; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <i class="v-icon notranslate mdi mdi-chart-bell-curve-cumulative" style="font-size: 18px;"></i>
      {$chartTitleLabel}
    </div>
    <div id="aichart_{$id}" style="min-height: 320px; width: 100%;"></div>
  </div>

  <!-- SECTION 6: NAVIGATION SHORTCUTS -->
  <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
    <button class="ai-nav-btn" data-route="{$modRoute}" style="background: {$modCol}15; color: {$modCol}; border: 1px solid {$modCol}40; border-radius: 10px; padding: 8px 14px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
      <i class="v-icon notranslate mdi {$modIco}" style="font-size: 14px;"></i>
      {$modRouteLbl}
    </button>
  </div>

  <!-- SIGNATURE FOOTER -->
  <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 22px; padding-top: 16px; border-top: 1px solid {$innerBorder}; font-size: 11.5px; color: {$subColor};">
    <i class="v-icon notranslate mdi mdi-shield-check" style="color: #42A5F5; font-size: 14px;"></i>
    {$footerLabel}
  </div>
</div>

<!-- SCRIPT BLOCK: SELF-EXECUTING VANILLA JAVASCRIPT APEXCHARTS (NEVER USE createApp OR Vue MOUNT) -->
<script>
(function(){
  function initChart(retries) {
    retries = retries || 0;
    var chartEl = document.getElementById('aichart_{$id}') || document.querySelector('#aichart_{$id}');
    var Apex = (typeof ApexCharts !== 'undefined' ? ApexCharts : (window.ApexCharts?.default || window.ApexCharts));
    if (!chartEl || !Apex) {
      if (retries < 25) { setTimeout(function(){ initChart(retries + 1); }, 75); }
      return;
    }
    if (window.__ai_charts && window.__ai_charts['aichart_{$id}']) {
      try { window.__ai_charts['aichart_{$id}'].destroy(); } catch(e) {}
    }
    try {
      var options = {
        chart: { type: 'bar', height: 320, background: 'transparent', toolbar: { show: false } },
        theme: { mode: '{$theme}' },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', horizontal: false } },
        series: [{ name: 'Total MAD', data: [/* EXTRACT NUMBERS e.g. 72029348.53, 40776416.95 */] }],
        xaxis: { categories: [/* EXTRACT NAMES e.g. 'Congele', 'Du frais' */], labels: { style: { colors: '{$subColor}' } } },
        yaxis: { labels: { style: { colors: '{$subColor}' }, formatter: function(val){ return Number(val).toLocaleString() + ' MAD'; } } },
        colors: ['{$modCol}', '#00897B', '#FFA726', '#AB47BC', '#26A69A', '#EF5350'],
        grid: { borderColor: '{$innerBorder}' },
        tooltip: { theme: '{$theme}', y: { formatter: function(val){ return Number(val).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' MAD'; } } }
      };
      var chart = new Apex(chartEl, options);
      chart.render();
      window.__ai_charts = window.__ai_charts || {};
      window.__ai_charts['aichart_{$id}'] = chart;
    } catch(err) {
      console.error('ApexCharts render error on aichart_{$id}:', err);
    }
  }
  initChart();
})();
</script>
PROMPT;
    }

    /**
     * Post-process and sanitize AI response to guarantee premium UI/UX and 100% language alignment.
     */
    private function beautifyAndValidateAIAnswer(string $answer, string $question, array $resultRows, int|string $id, string $theme, string $lang = 'en'): string
    {
        $answer = preg_replace('/^```(?:html|vue|xml)?\s*/i', '', trim($answer));
        $answer = preg_replace('/\s*```$/', '', $answer);

        $answer = preg_replace_callback('/<i\s+class="([^"]*)"/i', function ($m) {
            $cls = $m[1];
            if (str_contains($cls, 'mdi-') && !str_contains($cls, 'mdi ')) {
                $cls = 'v-icon notranslate mdi ' . $cls;
            }
            return '<i class="v-icon notranslate ' . trim($cls) . '"';
        }, $answer);

        if (str_contains($answer, '<table') && !str_contains($answer, 'ai-analytics-table')) {
            $answer = preg_replace('/<table(\s+class=")?/i', '<table class="ai-analytics-table ', $answer, 1);
        }

        if (str_contains($answer, '<table') && !str_contains($answer, 'overflow-x: auto')) {
            $answer = preg_replace('/(<table[\s\S]*?<\/table>)/i', '<div style="overflow-x: auto; width: 100%;">$1</div>', $answer);
        }

        // Language normalization for leaked French headers/labels when user communicates in English, Spanish, or Arabic
        if ($lang === 'en') {
            $replacements = [
                'Visualisation Graphique' => 'Graphical Visualization',
                'Visualisation graphique' => 'Graphical Visualization',
                'Réponse Directe' => 'Direct Answer',
                'Réponse directe' => 'Direct Answer',
                'Données Certifiées ERP • Source Unique de Vérité' => 'Certified ERP Data • Single Source of Truth',
                'Détails des Enregistrements' => 'Record Details & Metrics',
                'Total Enregistré' => 'Total Recorded',
                'Total Annuel' => 'Total Annual',
                'Module Production' => 'Production Module',
                'Module Charges' => 'Charges Module',
                'Module Ventes' => 'Sales Module',
                'Consulter' => 'View',
                'Détails' => 'Details',
                '>Janvier<' => '>January<',
                '>Février<' => '>February<',
                '>Mars<' => '>March<',
                '>Avril<' => '>April<',
                '>Mai<' => '>May<',
                '>Juin<' => '>June<',
                '>Juillet<' => '>July<',
                '>Août<' => '>August<',
                '>Septembre<' => '>September<',
                '>Octobre<' => '>October<',
                '>Novembre<' => '>November<',
                '>Décembre<' => '>December<',
                '"Janvier"' => '"January"',
                '"Février"' => '"February"',
                '"Mars"' => '"March"',
                '"Avril"' => '"April"',
                '"Mai"' => '"May"',
                '"Juin"' => '"June"',
                '"Juillet"' => '"July"',
                '"Août"' => '"August"',
                '"Septembre"' => '"September"',
                '"Octobre"' => '"October"',
                '"Novembre"' => '"November"',
                '"Décembre"' => '"December"',
            ];
            $answer = str_replace(array_keys($replacements), array_values($replacements), $answer);
        } elseif ($lang === 'es') {
            $replacements = [
                'Visualisation Graphique' => 'Visualización Gráfica',
                'Visualisation graphique' => 'Visualización Gráfica',
                'Réponse Directe' => 'Respuesta Directa',
                'Réponse directe' => 'Respuesta Directa',
                'Données Certifiées ERP • Source Unique de Vérité' => 'Datos Certificados ERP • Fuente Única de Verdad',
                'Détails des Enregistrements' => 'Detalles de Registros y Métricas',
                'Total Enregistré' => 'Total Registrado',
                'Total Annuel' => 'Total Anual',
                'Module Production' => 'Módulo Producción',
                'Module Charges' => 'Módulo Compras',
                'Module Ventes' => 'Módulo Ventas',
                'Consulter' => 'Ver',
                'Détails' => 'Detalles',
                '>Janvier<' => '>Enero<',
                '>Février<' => '>Febrero<',
                '>Mars<' => '>Marzo<',
                '>Avril<' => '>Abril<',
                '>Mai<' => '>Mayo<',
                '>Juin<' => '>Junio<',
                '>Juillet<' => '>Julio<',
                '>Août<' => '>Agosto<',
                '>Septembre<' => '>Septiembre<',
                '>Octobre<' => '>Octubre<',
                '>Novembre<' => '>Noviembre<',
                '>Décembre<' => '>Diciembre<',
                '"Janvier"' => '"Enero"',
                '"Février"' => '"Febrero"',
                '"Mars"' => '"Marzo"',
                '"Avril"' => '"Abril"',
                '"Mai"' => '"Mayo"',
                '"Juin"' => '"Junio"',
                '"Juillet"' => '"Julio"',
                '"Août"' => '"Agosto"',
                '"Septembre"' => '"Septiembre"',
                '"Octobre"' => '"Octubre"',
                '"Novembre"' => '"Noviembre"',
                '"Décembre"' => '"Diciembre"',
            ];
            $answer = str_replace(array_keys($replacements), array_values($replacements), $answer);
        } elseif ($lang === 'ar') {
            $replacements = [
                'Visualisation Graphique' => 'التمثيل البياني',
                'Visualisation graphique' => 'التمثيل البياني',
                'Réponse Directe' => 'إجابة مباشرة',
                'Réponse directe' => 'إجابة مباشرة',
                'Données Certifiées ERP • Source Unique de Vérité' => 'بيانات تخطيط موارد المؤسسات المعتمدة • مصدر الحقيقة الموثوق',
                'Consulter' => 'عرض',
                'Détails' => 'تفاصيل',
            ];
            $answer = str_replace(array_keys($replacements), array_values($replacements), $answer);
        }

        if (str_contains($answer, 'createApp(') && str_contains($answer, 'ApexCharts')) {
            $answer = preg_replace_callback('/<script>([\s\S]*?)<\/script>/i', function ($m) {
                $js = $m[1];
                if (str_contains($js, 'new ApexCharts')) {
                    return "<script>\n(function(){\n  try {\n    " . $js . "\n  } catch(e) { console.warn('AI Chart fallback error:', e); }\n})();\n</script>";
                }
                return $m[0];
            }, $answer);
        }

        // Ensure any ApexCharts script has resilient retry logic and responsive sizing
        if (str_contains($answer, 'new ApexCharts') && !str_contains($answer, 'initChart(')) {
            $answer = preg_replace_callback('/<script>([\s\S]*?)<\/script>/i', function ($m) {
                $js = $m[1];
                if (str_contains($js, 'new ApexCharts')) {
                    $chartId = '';
                    if (preg_match('/[\'"]#?(aichart_[^\'"]+)[\'"]/', $js, $idMatch)) {
                        $chartId = $idMatch[1];
                    }
                    $targetElExpr = $chartId ? "document.getElementById('{$chartId}') || document.querySelector('#{$chartId}')" : "document.querySelector('[id^=\"aichart_\"]')";
                    return "<script>\n(function(){\n  function initChart(retries) {\n    retries = retries || 0;\n    var Apex = (typeof ApexCharts !== 'undefined' ? ApexCharts : (window.ApexCharts?.default || window.ApexCharts));\n    var chartEl = {$targetElExpr};\n    if (!chartEl || !Apex) {\n      if (retries < 25) { setTimeout(function(){ initChart(retries + 1); }, 75); }\n      return;\n    }\n    try {\n      " . $js . "\n      if (typeof chart !== 'undefined' && chart && typeof chart.render === 'function') {\n        window.__ai_charts = window.__ai_charts || {};\n        window.__ai_charts['" . ($chartId ?: 'aichart') . "'] = chart;\n        window.dispatchEvent(new Event('resize'));\n      }\n    } catch(e) { console.warn('AI Chart execute error:', e); }\n  }\n  initChart();\n})();\n</script>";
                }
                return $m[0];
            }, $answer);
        }

        return trim($answer);
    }

    /**
     * Check if AI generated response satisfies enterprise design quality.
     */
    private function isAnswerSufficientlyPolished(?string $answer, array $resultRows = []): bool
    {
        if (!$answer || strlen(trim($answer)) < 80) {
            return false;
        }

        if (!str_contains($answer, '<div id="ai_')) {
            return false;
        }

        if (count($resultRows) >= 2) {
            $hasTable = str_contains($answer, '<table') && str_contains($answer, '<tr') && str_contains($answer, '<td');
            $hasChart = str_contains($answer, 'aichart_');

            if (!$hasTable && !$hasChart) {
                return false;
            }

            if (preg_match('/(catégorie|category|mois|month).*(total|prix|weight)/ui', $answer) && !str_contains($answer, '<table')) {
                return false;
            }
        }

        return true;
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
        APPLICATION: $this->app_name - Frozen Fish System
        DOMAIN: Fish buying, processing, storage, and selling in Morocco.
        CURRENCY: MAD (Moroccan Dirham)
        WEIGHT UNIT: KG (Kilograms)

        KEY BUSINESS ENTITIES:
        - CHARGES (purchases): When the company buys fish from suppliers (charges, detail_charges). Each charge has a date, supplier, ship, origin, fishery, tracking numbers, total_weight, total_price.
        - SHIPPINGS (sales): When the company sells / exports fish to clients (shippings, detail_shippings). Has date, client, ship, origin, total_weight, total_price.
        - PRODUCTIONS: Fish processing in factory (productions, detail_productions). Has fresh_reception_weight, frozen_production_weight, guano_production_weight, equilibrium.
        - WAREHOUSES: Storage locations with categories and balances (warehouses, warehouse_balances).
        - WORKFORCE: Factory workers and fortnight shifts (workforces, fortnight_activities, sections, corporals).
        - SUPPLIER INVOICES: Invoices for suppliers (supplier_invoices, supplier_payments).
        - CLIENT INVOICES: Invoices for clients (client_invoices, client_payments).
        - SUPPLIERS: Fish suppliers the company buys from.
        - CLIENTS: Buyers the company sells to.
        - TRUCKS: Transport vehicles used for moving fish.
        - SHIPS: Fishing vessels.
        - FISHERIES: Fishing zones/ports.
        - ORIGINS: Geographic origin of the fish.
        - FISH TYPES: Specific fish species (sardine, mackerel, etc.)
        - FISH FAMILIES: Groups of fish types.
        - CHARGE CATEGORIES: Types of charges.
        - FORMULAS: Pricing/calculation formulas.

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

        // Add model relationships, casts, and fillable fields
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

                    // Add fillable fields
                    $schema[$table]['fillable'] = $modelInstance->getFillable();

                    // Add casts — tells the AI the exact Laravel data type for each column
                    // e.g. 'array' means JSON array, 'boolean', 'decimal:2', 'date', etc.
                    $casts = $modelInstance->getCasts();
                    if (!empty($casts)) {
                        $schema[$table]['casts'] = $casts;

                        // Annotate JSON columns explicitly in the column list
                        foreach ($schema[$table]['columns'] as &$col) {
                            $field = $col['Field'];
                            if (isset($casts[$field])) {
                                $castType = $casts[$field];
                                if (in_array($castType, ['array', 'json', 'collection', 'object'])) {
                                    $col['LaravelType'] = 'json_array';
                                    $col['Note'] = 'This column stores a JSON array. When writing SQL, use JSON format: \'["value1","value2"]\'. When using Eloquent, pass a PHP array.';
                                } elseif ($castType === 'boolean') {
                                    $col['LaravelType'] = 'boolean';
                                } elseif (str_starts_with($castType, 'decimal')) {
                                    $col['LaravelType'] = $castType;
                                } elseif ($castType === 'date' || $castType === 'datetime') {
                                    $col['LaravelType'] = $castType;
                                }
                            }
                        }
                        unset($col);
                    }
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
     * Get the certified application route catalog directly synced with resources/js/router/routes.js.
     * Provides the AI with exact paths, route names, and purposes to guarantee 0% 404 errors.
     */
    private function getAppRoutes(): string
    {
        if (self::$cachedRoutes) {
            return self::$cachedRoutes;
        }

        $catalog = [
            'DASHBOARDS & CORE' => [
                ['path' => '/main-dashboard', 'name' => 'main_dashboard', 'desc' => 'Executive ERP Overview & Metrics (Tableau de Bord Principal)'],
                ['path' => '/jupiter-dashboard', 'name' => 'jupiter_dashboard', 'desc' => 'Jupiter Intelligence Analytics & Data Exploration'],
                ['path' => '/ai_prompt', 'name' => 'ai_prompt', 'desc' => 'Interactive AI Decision Workspace'],
            ],
            'WORKFORCE & QUINZAINES' => [
                ['path' => '/workforce/fortnight', 'name' => 'fortnight_management', 'desc' => 'Fortnight Sheets & Bi-monthly Payroll (Feuilles de Quinzaine Q1/Q2)'],
                ['path' => '/workforce/management', 'name' => 'workforce_management', 'desc' => 'Workforce Directory & Worker Profiles (Main d\'œuvre & Ouvriers)'],
                ['path' => '/workforce/corporals', 'name' => 'corporal_management', 'desc' => 'Corporals & Team Leaders (Caporaux & Équipes)'],
                ['path' => '/workforce/sections', 'name' => 'section_management', 'desc' => 'Factory Workshops & Sections (Ateliers & Sections Usine)'],
                ['path' => '/workforce/teams', 'name' => 'team_management', 'desc' => 'Factory Teams (Équipes de Travail)'],
                ['path' => '/workforce/statistics', 'name' => 'workforce_statistics', 'desc' => 'Workforce & Fortnight Statistics (Statistiques Quinzaine)'],
                ['path' => '/workforce/absence-movement', 'name' => 'workforce_absence_movement', 'desc' => 'Worker Absences & Daily Movements'],
            ],
            'PURCHASES & RECEPTIONS (CHARGES)' => [
                ['path' => '/charges/list_charge', 'name' => 'list_charge', 'desc' => 'Fish Purchases & Receptions List (Liste des Charges & Réceptions)'],
                ['path' => '/charges/add_charge', 'name' => 'add_charge', 'desc' => 'Register New Fish Reception / Charge (Nouvelle Charge)'],
                ['path' => '/charges', 'name' => 'charges', 'desc' => 'Charges Module Entrypoint (Redirects to list_charge)'],
            ],
            'SALES & EXPEDITIONS (SHIPPINGS)' => [
                ['path' => '/shippings/list_shipping', 'name' => 'list_shipping', 'desc' => 'Fish Sales & Shipments List (Liste des Expéditions & Ventes)'],
                ['path' => '/shippings/add_shipping', 'name' => 'add_shipping', 'desc' => 'Register New Fish Expedition / Sale (Nouvelle Expédition)'],
                ['path' => '/shippings', 'name' => 'shippings', 'desc' => 'Shippings Module Entrypoint (Redirects to list_shipping)'],
            ],
            'FACTORY PRODUCTION' => [
                ['path' => '/production/list', 'name' => 'production_list', 'desc' => 'Production Batches & Freezing List (Liste des Lots de Production)'],
                ['path' => '/production/add', 'name' => 'add_production', 'desc' => 'Create New Production Batch (Nouvelle Production)'],
                ['path' => '/production/charge-management', 'name' => 'charge_production_management', 'desc' => 'Assign Raw Charges to Production Lots'],
                ['path' => '/production', 'name' => 'production', 'desc' => 'Production Module Entrypoint (Redirects to production_list)'],
            ],
            'INVENTORY & WAREHOUSES' => [
                ['path' => '/warehouses', 'name' => 'warehouses', 'desc' => 'Cold Storage Chambers & Stock Levels (Entrepôts & Frigos)'],
                ['path' => '/storage-invoices', 'name' => 'storage_invoices', 'desc' => 'Cold Storage Occupancy Invoices (Factures Séjour Frigo)'],
                ['path' => '/inventory/list', 'name' => 'list_inventory', 'desc' => 'Inventory Count & Adjustment History (Inventaires)'],
                ['path' => '/inventory/add', 'name' => 'add_inventory', 'desc' => 'New Inventory Count (Nouvel Inventaire)'],
                ['path' => '/warehouse-transfers/list', 'name' => 'list_warehouse_transfer', 'desc' => 'Inter-Warehouse Transfers (Transferts Inter-Magasins)'],
                ['path' => '/order-transfers', 'name' => 'order_transfers_page', 'desc' => 'Order Transfers Management'],
            ],
            'INVOICING & SETTLEMENTS' => [
                ['path' => '/supplier_invoice/list_supplier_invoice', 'name' => 'list_supplier_invoice', 'desc' => 'Supplier Invoices (Factures Fournisseurs)'],
                ['path' => '/supplier_invoice/supplier_settlement', 'name' => 'supplier_settlement', 'desc' => 'Supplier Payments & Settlements (Règlements Fournisseurs)'],
                ['path' => '/client_invoice/list_client_invoice', 'name' => 'list_client_invoice', 'desc' => 'Client Invoices (Factures Clients)'],
                ['path' => '/client_invoice/client_settlement', 'name' => 'client_settlement', 'desc' => 'Client Payments & Settlements (Règlements Clients)'],
            ],
            'PARAMETERS & CONFIGURATION' => [
                ['path' => '/parameters/client-management', 'name' => 'client_management', 'desc' => 'Clients & Buyers Master Directory (Gestion Clients)'],
                ['path' => '/parameters/supplier-management', 'name' => 'supplier_management', 'desc' => 'Suppliers & Fishermen Master Directory (Gestion Fournisseurs)'],
                ['path' => '/parameters/fish-management', 'name' => 'fish_management', 'desc' => 'Fish Species & Commercial Sizes (Gestion des Espèces)'],
                ['path' => '/parameters/warehouse-management', 'name' => 'warehouse_management', 'desc' => 'Warehouse & Chamber Master Setup'],
                ['path' => '/parameters/charge-management', 'name' => 'charge_management', 'desc' => 'Charge Categories & Fees Setup'],
                ['path' => '/parameters/shipping-management', 'name' => 'shipping_management', 'desc' => 'Shipping & Export Categories Setup'],
                ['path' => '/parameters/production-management', 'name' => 'production_management', 'desc' => 'Production Categories & Yield Setup'],
                ['path' => '/parameters/cost-configuration-management', 'name' => 'cost_configuration_management', 'desc' => 'Industrial Cost & Margin Configuration'],
                ['path' => '/parameters/company-settings', 'name' => 'company_settings', 'desc' => 'Company Profile & General Settings'],
                ['path' => '/parameters/fortnight-settings', 'name' => 'fortnight_settings', 'desc' => 'Fortnight Calendar & Periods Settings'],
                ['path' => '/parameters/chart-management', 'name' => 'chart_management', 'desc' => 'Chart Settings & Metric Targets'],
            ],
        ];

        $output = "CERTIFIED APPLICATION ROUTE CATALOG (use ONLY these exact paths in navigation buttons data-route=\"...\"):\n\n";
        foreach ($catalog as $section => $routes) {
            $output .= "### {$section}\n";
            foreach ($routes as $r) {
                $output .= "- Path: `{$r['path']}` (Route Name: `{$r['name']}`) → {$r['desc']}\n";
            }
            $output .= "\n";
        }

        $output .= "CRITICAL NAVIGATION RULES:\n";
        $output .= "1. NEVER invent routes! For Quinzaines / Fortnight, ALWAYS use `/workforce/fortnight` (NEVER `/fortnight`).\n";
        $output .= "2. For Workers / Main-d'œuvre, ALWAYS use `/workforce/management` (NEVER `/workforces`).\n";
        $output .= "3. For Dashboard, ALWAYS use `/main-dashboard` (NEVER `/dashboard`).\n";
        $output .= "4. For Supplier Invoices, ALWAYS use `/supplier_invoice/list_supplier_invoice` (NEVER `/supplier_invoice`).\n";
        $output .= "5. For Client Invoices, ALWAYS use `/client_invoice/list_client_invoice` (NEVER `/client_invoice`).\n";
        $output .= "6. For Warehouse Transfers, ALWAYS use `/warehouse-transfers/list` (NEVER `/warehouse_transfer`).\n";
        $output .= "7. For Clients Directory, ALWAYS use `/parameters/client-management` (NEVER `/clients`).\n";
        $output .= "8. For Suppliers Directory, ALWAYS use `/parameters/supplier-management` (NEVER `/suppliers`).\n";

        self::$cachedRoutes = $output;
        return self::$cachedRoutes;
    }
}


