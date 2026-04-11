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

class AIQueryController extends Controller
{
    private CustomService $ai;

    private static ?string $cachedSchema = null;
    private static ?string $cachedComments = null;
    private ?string $app_name = null;

    public function __construct()
    {
        $this->ai = new CustomService();
        $this->app_name = env('APP_NAME');
    }

    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'id' => 'required|numeric',
            'theme' => 'required|string',
            'messages' => 'required|string',
        ]);

        $question = $data['message'];
        $id = $data['id'];
        $theme = $data['theme'];
        $messages = $data['messages'];

        // ── Step 1: Get schema ──
        $schema = $this->getSchemaString();
        $comments = $this->getTableCommentsString();
        $businessCtx = $this->getBusinessContext();

        // ── Step 2: Generate SQL ──
        $sqlPrompt = <<<PROMPT
        === DATABASE SCHEMA ===
        {$schema}

        === TABLE COMMENTS ===
        {$comments}

        === BUSINESS CONTEXT ===
        {$businessCtx}

        === RULES ===
        1. Generate ONLY a valid MySQL SELECT query — no markdown, no backticks, no explanation.
        2. Tables with SoftDelete: always add "WHERE deleted_at IS NULL".
        3. For pivot tables (charge_suppliers, charge_ships, truck_charge, sell_clients, sell_ships, truck_sell): use proper JOINs.
        4. Use COALESCE for nullable fields. LIMIT to 500 rows unless user asks for all.
        5. Currency is MAD, weight is KG.
        6. "charges" = purchases, "sells" = outgoing sales.

        === USER QUESTION ===
        {$question}
        PROMPT;

        $sqlQuery = $this->ai->generateSQL($sqlPrompt);

        if (!$sqlQuery) {
            return response()->json(['answer' => $this->buildFallbackAnswer($question, $id, $theme, $messages)], 200);
        }

        // ── Step 3: Execute SQL safely ──
        $result = null;
        try {
            // Safety: only allow SELECT
            $normalized = strtoupper(trim($sqlQuery));
            if (!str_starts_with($normalized, 'SELECT')) {
                return response()->json(['answer' => $this->buildFallbackAnswer($question, $id, $theme, $messages)], 200);
            }
            $result = DB::select($sqlQuery);
            $result = json_encode($result);
        } catch (\Exception $e) {
            $result = null;
        }

        // ── Step 4: Generate Vue/Vuetify/ApexCharts answer ──
        $answerPrompt = $this->buildVuePrompt($question, $result, $schema, $comments, $id, $theme, $messages, $businessCtx);
        $answer = $this->ai->generateAnswer($answerPrompt);

        if (!$answer) {
            $answer = $this->buildFallbackAnswer($question, $id, $theme, $messages);
        }

        // ── Step 5: Save to chat history ──
        $user = $request->user();
        if ($user) {
            ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
            if ($answer) {
                ChatMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
            }
        }

        return response()->json(['answer' => $answer], 200);
    }

    /**
     * Build the Vue.js/Vuetify/ApexCharts answer prompt.
     */
    private function buildVuePrompt(string $question, ?string $result, string $schema, string $comments, int $id, string $theme, string $messages, string $businessCtx): string
    {
        $dataSection = $result && $result !== '[]' && $result !== 'null'
            ? "Data from database (SINGLE source of truth, do NOT invent data): {$result}"
            : "The database returned NO data for this query. Tell the user there is no data available.";

        return <<<PROMPT
        === YOUR ROLE ===
        You are "$this->app_name AI", a professional data analyst for the $this->app_name fish management ERP.
        You generate Vue 3 + Vuetify 3 code that renders beautiful data visualizations.

        === BUSINESS CONTEXT ===
        {$businessCtx}

        === SCHEMA ===
        {$schema}

        === TABLE COMMENTS ===
        {$comments}

        === DATA ===
        {$dataSection}

        === RENDERING RULES ===
        IMPORTANT: The following libraries are ALREADY loaded in the page. Do NOT re-import them:
        - Vue 3 (via global `Vue` object, use `const { createApp } = Vue`)
        - Vuetify 3 (via global `vuetify` instance, already created)
        - ApexCharts (via global `ApexCharts` constructor)
        - MDI icons via CSS

        STRUCTURE your answer as ONLY this HTML pattern:
        <div id="ai_{$id}"><v-container class="rounded-xl"> <!-- YOUR VUETIFY TEMPLATE HERE --> </v-container></div>
        <script>
        const {{ createApp }} = Vue;
        createApp({{
          data() {{ return {{ /* your data */ }} }},
          mounted() {{ /* ApexCharts init if needed */ }}
        }}).use(vuetify).mount('#ai_{$id}');
        </script>

        RULES:
        1. NEVER fake data. Only use the data provided above.
        2. Detect user's language and respond in that language.
        3. Use {$theme} theme colors (dark = dark backgrounds, light = light backgrounds).
        4. Format numbers: '3 000.00' format. Currency: MAD. Weight: KG.
        5. For simple text answers: use v-card with v-card-text.
        6. For tabular data: use v-data-table with proper headers and items.
        7. For chart data (trends, comparisons): use ApexCharts with `new ApexCharts(document.querySelector('#aichart_{$id}'), options).render()`.
           - For chart div, use: <div id="aichart_{$id}"></div>
           - Initialize in mounted() hook.
        8. NEVER use v-table — use v-data-table instead.
        9. NEVER instantiate Vue or Vuetify again (no createVuetify).
        10. NEVER use fetch, axios, or any HTTP calls.
        11. NEVER use <body>, <html>, <head> tags.
        12. Make the design polished: use v-card, rounded corners, proper spacing.
        13. Give ONLY the HTML+script code. No markdown, no explanation.

        === USER QUESTION ===
        {$question}
        PROMPT;
    }

    /**
     * Fallback for when SQL fails or no data.
     */
    private function buildFallbackAnswer(string $question, int $id, string $theme, string $messages): string
    {
        $prompt = <<<PROMPT
        You are "$this->app_name AI" assistant for a fish management ERP.
        The user asked: "{$question}"
        Previous conversation: {$messages}

        The database query failed or returned no results.
        Generate a helpful Vue 3 + Vuetify 3 response explaining this.

        Use this structure:
        <div id="ai_{$id}"><v-container class="rounded-xl"><v-card class="rounded-xl" variant="flat"><v-card-text><!-- message --></v-card-text></v-card></v-container></div>
        <script>
        const {{ createApp }} = Vue;
        createApp({{}}).use(vuetify).mount('#ai_{$id}');
        </script>

        Use {$theme} theme. Be helpful and suggest the user rephrase.
        Give ONLY HTML+script. No markdown.
        PROMPT;

        return $this->ai->generateAnswer($prompt) ?? '<div>No data available. Please try rephrasing your question.</div>';
    }

    // ─────────────────────────────────────────
    //  Business Context
    // ─────────────────────────────────────────
    private function getBusinessContext(): string
    {
        return <<<CTX
        APP: $this->app_name — Frozen Fish (Morocco)
        CURRENCY: MAD | WEIGHT: KG
        - CHARGES = fish purchases from suppliers
        - SELLS = fish sales to clients
        - WAREHOUSES = storage with categories and operations
        - warehouse_stockable_tracking_number_operations = polymorphic ops (sourceable_type = Charge or Sell)
        - non_warehouse_stockable_operations = direct costs not through warehouse
        - SUPPLIERS, CLIENTS, TRUCKS, SHIPS, FISHERIES, ORIGINS = reference data
        - FISH TYPES → FISH FAMILIES (species grouping)
        - CHARGE CATEGORIES (is_production=true means production processing)
        - SUPPLIER INVOICES = consolidated billing
        CTX;
    }

    // ─────────────────────────────────────────
    //  Schema
    // ─────────────────────────────────────────
    private function getSchemaString(): string
    {
        if (self::$cachedSchema)
            return self::$cachedSchema;
        $tables = DB::select("SHOW TABLES");
        $tableNames = array_map('current', $tables);
        $schema = [];
        foreach ($tableNames as $table) {
            if (in_array($table, ['migrations', 'password_resets', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs', 'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions']))
                continue;
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $schema[$table] = array_map(fn($col) => ['Field' => $col->Field, 'Type' => $col->Type, 'Key' => $col->Key], $columns);
        }
        self::$cachedSchema = json_encode($schema, JSON_PRETTY_PRINT);
        return self::$cachedSchema;
    }

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

    public function getDatabaseSchema()
    {
        return response()->json(json_decode($this->getSchemaString(), true), Response::HTTP_OK, [], JSON_PRETTY_PRINT);
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
