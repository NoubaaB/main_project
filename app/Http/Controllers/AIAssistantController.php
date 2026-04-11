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
    private ?string $app_name = null;

    public function __construct()
    {
        $this->ai = new CustomService();
        $this->app_name = env('APP_NAME');
    }

    // ─────────────────────────────────────────
    //  Main endpoint: POST /api/ai-assistant
    // ─────────────────────────────────────────
    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'messages' => 'required|string',
        ]);

        $question = $data['message'];
        $messages = $data['messages'];

        // ── Step 1: Build context ──
        $schema = $this->getSchemaString();
        $tableComments = $this->getTableCommentsString();
        $businessCtx = $this->getBusinessContext();

        // ── Step 2: Generate SQL ──
        $sqlPrompt = $this->buildSQLPrompt($question, $messages, $schema, $tableComments, $businessCtx);
        $sqlQuery = $this->ai->generateSQL($sqlPrompt);

        // ── Step 3: Execute SQL safely ──
        $result = null;
        $sqlError = null;
        if ($sqlQuery) {
            try {
                // Safety: only allow SELECT
                if ($this->isSafeQuery($sqlQuery)) {
                    $result = DB::select($sqlQuery);
                    $result = json_encode($result);
                } else {
                    $sqlError = 'Query blocked: only SELECT statements are allowed.';
                }
            } catch (\Exception $e) {
                $sqlError = $e->getMessage();
            }
        }

        // ── Step 4: Generate human answer ──
        $answerPrompt = $this->buildAnswerPrompt($question, $messages, $schema, $tableComments, $result, $sqlError, $businessCtx);
        $answer = $this->ai->generateAnswer($answerPrompt);

        if (!$answer) {
            $answer = 'Sorry — I could not generate an answer. Please try rephrasing your question.';
        }

        // ── Step 5: Persist messages ──
        $user = $request->user();
        if ($user) {
            ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
            ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
        }

        return response()->json([
            'answer' => $answer,
            'result' => $result,
            'sqlQuery' => $sqlQuery,
        ], 200);
    }

    // ─────────────────────────────────────────
    //  Prompt Builders
    // ─────────────────────────────────────────

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
        2. Return ONLY the raw SQL — no markdown, no explanation, no backticks.
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
    private function buildAnswerPrompt(string $question, string $messages, string $schema, string $comments, ?string $result, ?string $sqlError, string $businessCtx): string
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
        You help users understand their business data — purchases, sales, stock, suppliers, and operations.

        === BUSINESS CONTEXT ===
        {$businessCtx}

        === SCHEMA (for reference) ===
        {$schema}

        === TABLE COMMENTS ===
        {$comments}

        === DATA ===
        {$dataSection}

        === RESPONSE RULES ===
        1. NEVER invent data. Only use the data provided above.
        2. Detect the user's language from their question and respond in the SAME language.
        3. Format all numbers with spaces as thousands separator: e.g., 12 500.00 MAD, 3 200.50 KG
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
        11. IMPORTANT NAV BUTTONS: When discussing specific records (like a Charge or a Supplier Invoice), ALWAYS provide an HTML button for the user to view it.
            - MUST use the class "ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1".
            - MUST include a "data-route" attribute with the exact path.
            - Format for Charge: <button class="..." data-route="/charges/view_charge/{id}">View Charge #{id}</button>
            - Format for Invoice: <button class="..." data-route="/supplier_invoice/view_supplier_invoice?id={id}">View Invoice</button>
            Place these buttons inline or inside table cells (e.g., an 'Action' column).


        === CONVERSATION HISTORY ===
        {$messages}

        === USER QUESTION ===
        {$question}
        PROMPT;
    }

    // ─────────────────────────────────────────
    //  Business Context
    // ─────────────────────────────────────────

    /**
     * Get the business context of the application.
     * This gives the AI a deep understanding of the domain.
     */
    private function getBusinessContext(): string
    {
        if (self::$cachedBusinessContext)
            return self::$cachedBusinessContext;

        self::$cachedBusinessContext = <<<CTX
        APPLICATION: $this->app_name — Frozen Fish System
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
        - CHARGE CATEGORIES: Types of charges (some are "production" type — where is_production=true).
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

    // ─────────────────────────────────────────
    //  Safety
    // ─────────────────────────────────────────

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

    // ─────────────────────────────────────────
    //  Schema Extraction
    // ─────────────────────────────────────────

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

    // ─────────────────────────────────────────
    //  Model/Relationship Introspection
    // ─────────────────────────────────────────

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
}
