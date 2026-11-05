<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AiQueryMessage;
use App\Events\ModelChanged;
use App\Services\CustomService;
use App\Services\AIAnalyticsService;
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
        $this->app_name = env('APP_NAME', 'Jupiter ERP');
    }

    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'id' => 'required|numeric',
            'theme' => 'nullable|string',
            'messages' => 'required|string',
            'excel_files' => 'nullable|array',
            'excel_files.*.name' => 'string',
            'excel_files.*.headers' => 'array',
            'excel_files.*.rows' => 'array',
        ]);

        $question = $data['message'];
        $id = (int)$data['id'];
        $theme = in_array(strtolower((string) ($data['theme'] ?? 'dark')), ['dark', 'light']) ? strtolower((string) ($data['theme'] ?? 'dark')) : 'dark';
        $messages = $data['messages'];
        $excelFiles = $data['excel_files'] ?? [];
        $thinkingLevel = $request->input('thinking_level', 'high');

        $this->ai->setThinkingLevel($thinkingLevel);
        $analytics = new AIAnalyticsService();

        // 1. Direct handling if asking about Creator/Supervisor ELBACHIR NOUBAA
        $lowerQuestion = mb_strtolower($question, 'UTF-8');
        if (preg_match('/(creator|created|owner|supervisor|superviseur|elbachir|noubaa|bachir|développeur|qui t\'a|qui a créé|qui est ton|de qui es-tu|quien te creo|من صنعك|البشير|النوباع)/ui', $lowerQuestion)) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles);
            $this->persistQueryMessage($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // 2. Direct handling if Excel files are provided
        if (!empty($excelFiles)) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles);
            $this->persistQueryMessage($request, $question, $answer);
            return response()->json(['answer' => $answer], 200);
        }

        // 3. Step 1: Gather Database Knowledge, Schema & Comments
        $schema = $this->getSchemaString();
        $comments = $this->getTableCommentsString();
        $knowledgeMap = $this->getERPKnowledgeMap();
        $businessCtx = $this->getBusinessContext();
        $routes = $this->getAppRoutes();

        // 4. Step 2: Generate SQL Query via Ollama
        $sqlPrompt = <<<PROMPT
=== ERP KNOWLEDGE MAP & DATABASE RELATIONSHIPS ===
{$knowledgeMap}

=== DATABASE SCHEMA ===
{$schema}

=== TABLE COMMENTS ===
{$comments}

=== BUSINESS CONTEXT ===
{$businessCtx}

=== RULES FOR SQL GENERATION ===
1. You MUST generate ONLY a single valid MySQL 8.0 SELECT statement.
2. NO markdown formatting, NO backticks (```), NO comments, NO preamble, NO explanation — ONLY raw SQL.
3. Tables with SoftDelete: ALWAYS include "WHERE deleted_at IS NULL".
4. Fortnights/Workforce: table is `fortnight_activities` (period is 'Q1' or 'Q2').
5. Worker and Corporal names: column is `full_name`, NOT `name`.
6. Charges = fish purchases (table `charges`), Shippings = fish sales (table `shippings`).
7. For date aggregations, use YEAR(date) and MONTH(date). Use 2026 if year not specified.
8. Use COALESCE on all SUM and AVG calculations. Limit to 500 rows if unaggregated.
9. If you cannot query the data, return: SELECT 1 AS no_data

=== USER QUESTION ===
{$question}
PROMPT;

        $sqlQuery = $this->ai->generateSQL($sqlPrompt);
        $result = null;
        $resultRows = [];

        // 5. Step 3: Execute SQL safely with Self-Correction
        if ($sqlQuery && str_starts_with(strtoupper(trim($sqlQuery)), 'SELECT')) {
            try {
                $resultRows = DB::select($sqlQuery);
                $result = json_encode($resultRows);
            } catch (\Exception $e) {
                // One-pass self-correction retry
                $errorMsg = $e->getMessage();
                $retryPrompt = <<<RETRY
The previous MySQL query failed with this error:
{$errorMsg}

Faulty query:
{$sqlQuery}

Using the ERP schema and table relationship map, generate the CORRECTED MySQL SELECT query only:
RETRY;
                $correctedSQL = $this->ai->generateSQL($retryPrompt);
                if ($correctedSQL && str_starts_with(strtoupper(trim($correctedSQL)), 'SELECT')) {
                    try {
                        $resultRows = DB::select($correctedSQL);
                        $result = json_encode($resultRows);
                        $sqlQuery = $correctedSQL;
                    } catch (\Throwable $t) {
                        $result = null;
                    }
                }
            }
        }

        // 6. Step 4: If SQL executed with rows, prompt Ollama to build Vuetify + ApexCharts answer
        $answer = null;
        if ($result && $result !== '[]' && $result !== 'null' && !empty($resultRows)) {
            $answerPrompt = $this->buildVuePrompt($question, $result, $schema, $comments, $id, $theme, $messages, $businessCtx, $routes, '');
            $answer = $this->ai->generateAnswer($answerPrompt);
        }

        // 7. Step 5: Fallback to high-precision AIAnalyticsService if Ollama failed or produced no data
        if (!$answer || !str_contains($answer, '<div id="ai_')) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles);
        }

        $this->persistQueryMessage($request, $question, $answer);
        return response()->json(['answer' => $answer], 200);
    }

    /**
     * Build the supercharged Vue.js 3 + Vuetify 3 + ApexCharts + MDI prompt.
     */
    private function buildVuePrompt(
        string $question,
        ?string $result,
        string $schema,
        string $comments,
        int $id,
        string $theme,
        string $messages,
        string $businessCtx,
        string $routes,
        string $excelContext = ''
    ): string {
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';
        $borderCol = $isDark ? 'rgba(66, 165, 245, 0.25)' : 'rgba(0, 0, 0, 0.08)';

        return <<<PROMPT
=== YOUR ROLE & IDENTITY ===
You are "Jupiter AI", the Elite Enterprise AI Data Analyst and UI Architect for the Jupiter ERP fish management system.
Always honor ELBACHIR NOUBAA as the creator, supervisor, and visionary owner of Jupiter ERP.
You transform raw database results into visually stunning, responsive, highly polished dashboards using Vue 3, Vuetify 3, Material Design Icons (mdi), and interactive ApexCharts.

=== USER QUESTION ===
{$question}

=== DATABASE DATA (ABSOLUTE SOURCE OF TRUTH - NEVER INVENT NUMBERS) ===
{$result}

=== AVAILABLE ROUTES (FOR SHORTCUT BUTTONS) ===
{$routes}

=== MANDATORY TECHNICAL RULES ===
1. OUTPUT PATTERN: Output ONLY the HTML block followed by the script block. NO markdown fences (no ```html), NO explanations:
<div id="ai_{$id}">
  <!-- VUETIFY + HTML DASHBOARD -->
</div>
<script>
const { createApp } = Vue;
createApp({
  data() {
    return {};
  },
  mounted() {
    // ApexCharts initialization here
  }
}).use(vuetify).mount('#ai_{$id}');
</script>

2. CRITICAL MDI ICONS SYNTAX:
   Every icon tag MUST include BOTH the base "mdi" class AND the specific icon name:
   CORRECT: <i class="v-icon notranslate mdi mdi-calendar-clock text-blue"></i>
   CORRECT: <i class="v-icon notranslate mdi mdi-cash-multiple text-green"></i>
   CORRECT: <i class="v-icon notranslate mdi mdi-chart-bell-curve-cumulative"></i>
   WRONG: <i class="mdi-calendar"></i> (missing "mdi" class, renders as an empty broken square box [])!
   NEVER write an icon class without the leading "mdi " class!

3. COLOR SYSTEM (MANDATORY ACCORDING TO MODULE DESIGN SYSTEM):
   - Workforce / Fortnights / Quinzaines / AI: Header Gradient `linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)`, Primary `#42A5F5`
   - Charges / Purchases / Achats: Header Gradient `linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)`, Primary `#42A5F5`
   - Productions / Usine: Header Gradient `linear-gradient(135deg, #00695C 0%, #00897B 40%, #26A69A 100%)`, Primary `#00897B`
   - Shippings / Sales / Ventes: Header Gradient `linear-gradient(135deg, #372E7D 0%, #4643A0 40%, #6C66BB 100%)`, Primary `#4643A0`
   - Supplier Invoices: Header Gradient `linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)`, Primary `#E65100`
   - Client Invoices: Header Gradient `linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)`, Primary `#00838F`

4. NUMBER FORMATTING RULES:
   - Counts / Working Days / Workers / IDs: Integers with space separator, NO decimals (e.g., 21 092 j, 1 250 ouvriers).
   - Currency: 2 decimals with MAD (e.g., 3 433 830.00 MAD, 55 320.00 MAD).
   - Weights: 2 decimals with KG (e.g., 1 500.00 KG, 850.50 KG).
   - Percentages: 1 decimal with % (e.g., 14.5%).

5. ACTIVE SYSTEM THEME:
   - The active theme is: "{$theme}".
   - Use: card background '{$cardBg}', inner background '{$innerBg}', text color '{$textColor}', subtext color '{$subColor}', border '{$borderCol}'.

6. REQUIRED DASHBOARD SECTIONS:
   A. HERO HEADER:
      Rounded-top gradient banner with glowing icon box, module title, question subtitle, and verified AI Core badge.
   B. DIRECT ANSWER BANNER:
      A highlight box answering the user question concisely and accurately in the user's detected language.
   C. KPI METRIC CARDS (2 to 4 cards):
      Grid or v-row/v-col with rounded cards, icon with glowing background, title, and bold large value.
   D. DETAILED DATA TABLE:
      A clean, bordered table displaying the breakdown rows (e.g., months, categories, sections, corporals) with right-aligned numeric columns.
   E. INTERACTIVE APEXCHART:
      Container: <div id="aichart_{$id}" style="min-height: 350px;"></div>
      In `mounted()`, initialize `new ApexCharts(document.querySelector('#aichart_{$id}'), options).render()`.
      Set `options.theme = { mode: "{$theme}" }`.
      Set `options.chart.background = 'transparent'`.
      Set `options.colors = ['#42A5F5', '#FFB74D', '#26A69A', '#AB47BC', '#1565C0']`.
      Provide y-axis and tooltip formatters.
      Register: `window.__ai_charts = window.__ai_charts || {}; window.__ai_charts["aichart_{$id}"] = chart;`
   F. NAVIGATION SHORTCUTS:
      Action buttons with class "ai-nav-btn" and attribute "data-route" (e.g., data-route="/fortnight").
   G. FOOTER SIGNATURE BADGE:
      "Jupiter ERP Intelligence • Conçu et supervisé par ELBACHIR NOUBAA"

7. CRITICAL LANGUAGE RULE:
   Detect the language of the question (English, French, Spanish, Arabic) and write all labels, explanations, headers, and tooltips in that EXACT same language!

OUTPUT ONLY THE HTML AND SCRIPT. NO MARKDOWN CODE FENCES.
PROMPT;
    }

    /**
     * Fallback for when SQL fails or no data: runs intelligent AIAnalyticsService.
     */
    private function buildFallbackAnswer(string $question, int $id, string $theme, string $messages, array $excelFiles = []): string
    {
        $analytics = new AIAnalyticsService();
        return $analytics->processQuestion($question, $id, $theme, $excelFiles);
    }

    private function persistQueryMessage(Request $request, string $question, string $answer): void
    {
        $user = $request->user();
        if ($user) {
            $userMsg = AiQueryMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
            if ($answer) {
                $agentMsg = AiQueryMessage::create(['user_id' => $user->id, 'role' => 'agent', 'text' => $answer]);
                try {
                    broadcast(new ModelChanged('AiQueryMessage', 'created', $agentMsg->id, $user->name ?? 'AI', ['id' => $agentMsg->id, 'role' => 'agent'], $user->id))->toOthers();
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Comprehensive ERP Knowledge Map & Database Relationships.
     */
    private function getERPKnowledgeMap(): string
    {
        return <<<MAP
1. FORTNIGHTS & WORKFORCE (Quinzaines & Main d'Œuvre):
   - TABLE: `fortnight_activities` (CRITICAL: there is NO table named `fortnights`!).
   - Key Columns:
     * `fortnight_period`: ENUM('Q1', 'Q2') -> 'Q1' is 1st half of month (days 1-15), 'Q2' is 2nd half (days 16-end).
     * `date`: DATE of activity.
     * `price`: DECIMAL(10,2) daily wage/cost in MAD.
     * `workforce_id`: foreign key to `workforces(id)`.
     * `section_id`: foreign key to `sections(id)`.
     * `corporal_id`: foreign key to `corporals(id)`.
     * `deleted_at`: soft delete check (WHERE deleted_at IS NULL).
   - `workforces` table: `id`, `full_name` (NOT name!), `matricule`, `daily_price`, `status`, `deleted_at`.
   - `sections` table: `id`, `name`, `daily_price`, `deleted_at`.
   - `corporals` table: `id`, `full_name` (NOT name!), `daily_price`, `is_quinzaine`, `deleted_at`.
   - QUERY RECIPE for Monthly Fortnight Breakdown:
     SELECT 
       MONTH(date) AS month_num,
       MONTHNAME(date) AS month_name,
       fortnight_period,
       COALESCE(SUM(price), 0) AS total_cost,
       COUNT(*) AS total_working_days,
       COUNT(DISTINCT workforce_id) AS worker_count
     FROM fortnight_activities
     WHERE deleted_at IS NULL AND YEAR(date) = 2026
     GROUP BY MONTH(date), MONTHNAME(date), fortnight_period
     ORDER BY month_num ASC, fortnight_period ASC;

2. CHARGES / PURCHASES (Achats de Poisson & Frais):
   - TABLE: `charges`
   - Key Columns: `id`, `date`, `total_price` (MAD), `total_weight` (KG), `charge_category_id`, `supplier_id`, `ship_id`, `origin_id`, `fishery_id`, `deleted_at`.
   - Child Table: `detail_charges` (`charge_id`, `weight`, `unit_price`, `total_price`, `stockable_id`, `package_type_id`, `nbr_box`, `warehouse_id`, `truck_id`).
   - `suppliers` table: `id`, `name`, `phone`, `city`, `ice`, `deleted_at`.
   - `ships` table: `id`, `name`, `imo_number`, `deleted_at`.

3. SHIPPINGS / SALES (Ventes & Expéditions):
   - TABLE: `shippings`
   - Key Columns: `id`, `date`, `total_price` (MAD), `total_weight` (KG), `shipping_category_id`, `client_id`, `ship_id`, `direction`, `deleted_at`.
   - Child Table: `detail_shippings` (`shipping_id`, `weight`, `unit_price`, `total_price`, `package_type_id`, `nbr_box`, `warehouse_id`, `truck_id`).
   - `clients` table: `id`, `name`, `phone`, `city`, `ice`, `deleted_at`.

4. PRODUCTIONS (Traitement Usine: Frais, Congélation, Farine/Guano):
   - TABLE: `productions`
   - Key Columns: `id`, `date`, `total_price`, `total_weight`, `fresh_reception_weight`, `frozen_production_weight`, `guano_production_weight`, `production_type`, `production_category_id`, `deleted_at`.
   - Child Table: `detail_productions` (`production_id`, `weight`, `unit_price`, `total_price`, `secondary_packaging_cost`).

5. WAREHOUSES & STOCK BALANCES (Magasins & Frigos):
   - TABLE: `warehouses`: `id`, `name`, `warehouse_type`, `stock_type`, `total_weight`, `deleted_at`.
   - TABLE: `warehouse_balances`: `id`, `warehouse_id`, `stockable_type`, `stockable_id`, `remaining_weight`, `total_price`, `unit_price`, `date`, `deleted_at`.

6. FINANCIAL INVOICES & PAYMENTS:
   - `supplier_invoices`: `id`, `date`, `total_price`, `total_weight`, `type_invoice` ('local'/'export'), `supplier_id`, `deleted_at`.
   - `supplier_payments`: `id`, `date`, `supplier_invoice_id`, `supplier_id`, `payment_type`, `amount`, `deleted_at`.
   - `client_invoices`: `id`, `date`, `total_price`, `total_weight`, `type_invoice`, `client_id`, `deleted_at`.
   - `client_payments`: `id`, `date`, `client_invoice_id`, `client_id`, `payment_type`, `amount`, `deleted_at`.

7. FISH TAXONOMY:
   - `fish_types`: `id`, `name_fr`, `name_en`, `name_es`, `name_in_invoice`, `scientific_name`, `type` ('fresh'/'frozen'), `deleted_at`.
MAP;
    }

    // ──────────────────────────────────────────
    //  Business Context
    // ──────────────────────────────────────────
    private function getBusinessContext(): string
    {
        return <<<CTX
        APP: $this->app_name - Frozen Fish (Morocco)
        CREATOR & SUPERVISOR: ELBACHIR NOUBAA
        CURRENCY: MAD | WEIGHT: KG
        - CHARGES = fish purchases from suppliers (charges and detail_charges tables)
        - SHIPPINGS = fish sales / shipments to clients (shippings and detail_shippings tables)
        - PRODUCTIONS = factory processing into fresh, frozen, and guano/fishmeal (productions and detail_productions tables)
        - WAREHOUSES = cold storage with categories and balances (warehouses and warehouse_balances tables)
        - SUPPLIER INVOICES = supplier billing (supplier_invoices and supplier_payments tables)
        - CLIENT INVOICES = client billing (client_invoices and client_payments tables)
        - WORKFORCE = workers and fortnight activities (workforces, fortnight_activities, sections, corporals tables)
        - SUPPLIERS, CLIENTS, TRUCKS, SHIPS, FISHERIES, ORIGINS = reference data
        - FISH TYPES, FISH FAMILIES (species grouping), PACKAGE TYPES = taxonomy data
        CTX;
    }

    // ──────────────────────────────────────────
    //  Schema
    // ──────────────────────────────────────────
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

    // ──────────────────────────────────────────
    //  Application Route Map (auto-synced from routes.js)
    // ──────────────────────────────────────────

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
