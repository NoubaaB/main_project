<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AiQueryMessage;
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
    private static ?string $cachedRoutes = null;
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
            'excel_files' => 'nullable|array',
            'excel_files.*.name' => 'string',
            'excel_files.*.headers' => 'array',
            'excel_files.*.rows' => 'array',
        ]);

        $question = $data['message'];
        $id = $data['id'];
        $theme = $data['theme'];
        $messages = $data['messages'];
        $excelFiles = $data['excel_files'] ?? [];

        // "" Step 1: Get schema ""
        $schema = $this->getSchemaString();
        $comments = $this->getTableCommentsString();
        $businessCtx = $this->getBusinessContext();

        // "" Build Excel context if files provided ""
        $excelContext = '';
        if (!empty($excelFiles)) {
            $excelContext = "\n\n=== UPLOADED EXCEL FILES ===\n";
            foreach ($excelFiles as $i => $file) {
                $fileName = $file['name'] ?? 'File ' . ($i + 1);
                $headers = $file['headers'] ?? [];
                $rows = $file['rows'] ?? [];
                $rowCount = count($rows);
                $preview = array_slice($rows, 0, 50); // Send up to 50 rows as preview
                $excelContext .= "\nFile: {$fileName} ({$rowCount} rows)\n";
                $excelContext .= "Headers: " . implode(', ', $headers) . "\n";
                $excelContext .= "Data (first " . min(50, $rowCount) . " rows):\n";
                foreach ($preview as $row) {
                    $excelContext .= json_encode($row) . "\n";
                }
            }
            $excelContext .= "\nIMPORTANT: The user has uploaded Excel file(s). Use this data to answer their question. You can analyze, summarize, compare, filter, or visualize this data. If the user asks about both Excel and database data, combine them.\n";
        }

        // "" Step 2: Generate SQL (or analyze Excel) ""
        $sqlPrompt = <<<PROMPT
        === DATABASE SCHEMA ===
        {$schema}

        === TABLE COMMENTS ===
        {$comments}

        === BUSINESS CONTEXT ===
        {$businessCtx}
        {$excelContext}

        === RULES ===
        1. Generate ONLY a valid MySQL SELECT query " no markdown, no backticks, no explanation.
        2. Tables with SoftDelete: always add "WHERE deleted_at IS NULL".
        3. For pivot tables (charge_suppliers, charge_ships, truck_charge, sell_clients, sell_ships, truck_sell): use proper JOINs.
        4. Use COALESCE for nullable fields. LIMIT to 500 rows unless user asks for all.
        5. Currency is MAD, weight is KG.
        6. "charges" = purchases, "sells" = outgoing sales.
        7. If the user's question is ONLY about the uploaded Excel file(s) and does NOT need database data, respond with exactly: EXCEL_ONLY

        === USER QUESTION ===
        {$question}
        PROMPT;

        $sqlQuery = $this->ai->generateSQL($sqlPrompt);

        if (!$sqlQuery) {
            if (!empty($excelFiles)) {
                // No SQL but we have Excel data " go straight to answer generation
                $sqlQuery = 'EXCEL_ONLY';
            } else {
                return response()->json(['answer' => $this->buildFallbackAnswer($question, $id, $theme, $messages)], 200);
            }
        }

        // "" Step 3: Execute SQL safely (skip if Excel-only) ""
        $result = null;
        $isExcelOnly = strtoupper(trim($sqlQuery)) === 'EXCEL_ONLY';

        if (!$isExcelOnly) {
            try {
                // Safety: only allow SELECT
                $normalized = strtoupper(trim($sqlQuery));
                if (!str_starts_with($normalized, 'SELECT')) {
                    if (!empty($excelFiles)) {
                        $isExcelOnly = true;
                    } else {
                        return response()->json(['answer' => $this->buildFallbackAnswer($question, $id, $theme, $messages)], 200);
                    }
                } else {
                    $result = DB::select($sqlQuery);
                    $result = json_encode($result);
                }
            } catch (\Exception $e) {
                $result = null;
            }
        }

        // "" Step 4: Generate Vue/Vuetify/ApexCharts answer ""
        $routes = $this->getAppRoutes();
        $answerPrompt = $this->buildVuePrompt($question, $result, $schema, $comments, $id, $theme, $messages, $businessCtx, $routes, $excelContext);
        $answer = $this->ai->generateAnswer($answerPrompt);

        if (!$answer) {
            $answer = $this->buildFallbackAnswer($question, $id, $theme, $messages);
        }

        // "" Step 5: Save to ai_query_messages ""
        $user = $request->user();
        if ($user) {
            AiQueryMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
            if ($answer) {
                AiQueryMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
            }
        }

        return response()->json(['answer' => $answer], 200);
    }

    /**
     * Build the Vue.js/Vuetify/ApexCharts answer prompt.
     */
    private function buildVuePrompt(string $question, ?string $result, string $schema, string $comments, int $id, string $theme, string $messages, string $businessCtx, string $routes, string $excelContext = ''): string
    {
        $dataSection = $result && $result !== '[]' && $result !== 'null'
            ? "Data from database (SINGLE source of truth, do NOT invent data): {$result}"
            : ($excelContext ? "No database query was needed - use the Excel data below." : "The database returned NO data for this query. Tell the user there is no data available.");

        if ($excelContext) {
            $dataSection .= "\n\n" . $excelContext;
        }

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

        === APPLICATION ROUTES (all available pages) ===
        {$routes}

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
        4. Format numbers with spaces as thousands separator. CRITICAL FORMATTING RULES:
           - COUNTS (quantities, number of records, IDs, number of items): display as INTEGERS with NO decimals. Example: 1 250 records, 35 items, ID #12.
           - PRICES (amounts, costs, totals in MAD): display with exactly 2 decimals. Example: 12 500.00 MAD, 3 200.50 MAD.
           - WEIGHTS (kg values): display with exactly 2 decimals. Example: 1 500.00 KG, 850.75 KG.
           - PERCENTAGES: display with 1 decimal. Example: 85.3%, 12.0%.
        5. For simple text answers: use v-card with v-card-text.
        6. For tabular data: use v-data-table with proper headers and items.
        7. For chart data (trends, comparisons): use ApexCharts with `new ApexCharts(document.querySelector('#aichart_{$id}'), options).render()`.
           - For chart div, use: <div id="aichart_{$id}"></div>
           - Initialize in mounted() hook.
        8. NEVER use v-table " use v-data-table instead.
        9. NEVER instantiate Vue or Vuetify again (no createVuetify).
        10. NEVER use fetch, axios, or any HTTP calls.
        11. NEVER use <body>, <html>, <head> tags.
        12. Make the design polished: use v-card, rounded corners, proper spacing.
        13. Give ONLY the HTML+script code. No markdown, no explanation.
        14. IMPORTANT NAV BUTTONS: Use the APPLICATION ROUTES map above to provide navigation buttons.
            - When discussing specific records (Charge, Sell, Invoice, etc.) or when the user asks "where can I...", "how do I go to...", "take me to...", ALWAYS provide a clickable button.
            - MUST use the class "ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1".
            - MUST include a "data-route" attribute with the exact path from the route map.
            - NEVER use @click or v-on:click attributes. The click is handled automatically via data-route.
            - For routes with dynamic params like :id, substitute the real ID from the data.
            - Examples:
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/charges/view_charge/123">View Charge #123</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/charges/add_charge">Add New Charge</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/production/kanban">Production Kanban</button>
              <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small bg-primary text-white ml-2 rounded-pill px-4 py-1" data-route="/parameters/fish-management">Fish Management</button>
            - Place buttons inline or inside table cells (e.g., an 'Action' column).
            - If the user asks for a page/link/direction, provide all relevant buttons from the route map.

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

    // """""""""""""""""""""""""""""""""""""""""
    //  Business Context
    // """""""""""""""""""""""""""""""""""""""""
    private function getBusinessContext(): string
    {
        return <<<CTX
        APP: $this->app_name " Frozen Fish (Morocco)
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

    // """""""""""""""""""""""""""""""""""""""""
    //  Schema
    // """""""""""""""""""""""""""""""""""""""""
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

    private function parseJsRoutes(string $content): array
    {
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

            if (preg_match('/children\s*:\s*\[/', $t)) {
                $pathStack[] = $lastPath ?? '';
                $childrenDepthMarkers[] = $depth;
            }

            if (preg_match('/path:\s*["\']([^"\']*)["\']/', $t, $m)) {
                $lastPath = $m[1];
            }

            if (preg_match('/name:\s*["\']([^"\']*)["\']/', $t, $m) && !str_contains($t, 'redirect')) {
                $routes[] = [
                    'path' => $this->resolveRoutePath($pathStack, $lastPath),
                    'name' => $m[1],
                ];
            }

            $depth += substr_count($t, '[') + substr_count($t, '{');
            $depth -= substr_count($t, ']') + substr_count($t, '}');

            while (!empty($childrenDepthMarkers) && $depth <= end($childrenDepthMarkers)) {
                array_pop($pathStack);
                array_pop($childrenDepthMarkers);
            }
        }

        return $routes;
    }

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

    private function stripJsFunctionBodies(string $content): string
    {
        $result = '';
        $len = strlen($content);
        $i = 0;

        while ($i < $len) {
            if ($i + 8 <= $len && substr($content, $i, 8) === 'function') {
                $j = $i + 8;
                while ($j < $len && $content[$j] !== '{')
                    $j++;
                if ($j >= $len) {
                    $result .= substr($content, $i);
                    break;
                }
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


