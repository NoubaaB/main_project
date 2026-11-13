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
        $isComplex = $analytics->isComplexAnalyticsQuestion($question);
        $cleanHistory = $this->formatCleanConversationHistory($messages);

        // 1. Direct handling if asking about System Architecture & AI Core
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
10. AVOID DUPLICATES: When counting parent records (vouchers, charges, shippings), ALWAYS use COUNT(DISTINCT parent.id). NEVER do COUNT(*) when joining 1:N detail tables (like detail_charges, charge_suppliers) as it duplicates counts!
11. VOUCHERS / BONS: A charge voucher is a row in table `charges` where `reception_tracking_number` holds the voucher/bon number.
12. Resolve pronouns, references, and follow-ups using the RECENT CONVERSATION CONTEXT below.

=== RECENT CONVERSATION CONTEXT ===
{$cleanHistory}

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

        // 6. Step 4: Prompt Ollama for response according to question complexity
        $answer = null;
        if ($result && $result !== '[]' && $result !== 'null' && !empty($resultRows)) {
            if (!$isComplex) {
                // Regular / simple factual question -> Direct Plain Text Answer
                $plainPrompt = $this->buildPlainTextPrompt($question, $result, $cleanHistory, $businessCtx, $sqlQuery);
                $rawAnswer = $this->ai->generatePlainTextAnswer($plainPrompt);
                if ($rawAnswer && strlen(trim($rawAnswer)) > 5) {
                    $answer = trim($rawAnswer);
                }
            } else {
                // Complex question (KPIs, Dashboards, Charts) -> Rich Vuetify + ApexCharts Card
                $answerPrompt = $this->buildVuePrompt($question, $result, $schema, $comments, $id, $theme, $cleanHistory, $businessCtx, $routes, '', $sqlQuery);
                $rawAnswer = $this->ai->generateAnswer($answerPrompt);
                if ($rawAnswer) {
                    $answer = $this->beautifyAndValidateAIAnswer($rawAnswer, $question, $resultRows, $id, $theme);
                }
            }
        }

        // 7. Step 5: Fallback to high-precision AIAnalyticsService if Ollama failed or produced degraded output
        if (!$isComplex) {
            if (!$answer || strlen(trim($answer)) < 3) {
                $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles);
            }
        } else {
            if (!$answer || !$this->isAnswerSufficientlyPolished($answer, $resultRows)) {
                $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles);
            }
        }

        $this->persistQueryMessage($request, $question, $answer);
        return response()->json(['answer' => $answer], 200);
    }

    /**
     * Resolve module design tokens according to claude.md and BluePrintFish design system.
     */
    private function resolveModuleDesign(string $question, ?string $sql = null): array
    {
        $text = mb_strtolower($question . ' ' . ($sql ?? ''), 'UTF-8');

        // 1. Charge / Purchase Module
        if (preg_match('/(charge|achat|reception|supplier|fournisseur|achat_frais|rsw|glace|emballage)/ui', $text)) {
            return [
                'name' => 'Module Achats & Charges',
                'color' => '#42A5F5',
                'gradient' => 'linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)',
                'icon' => 'mdi-truck-delivery',
                'route' => '/charges',
                'route_label' => 'Module Achats',
            ];
        }

        // 2. Shipping / Sales Module
        if (preg_match('/(shipping|vente|expedition|client|sale|facture_client|glace_client|emballage_client)/ui', $text)) {
            return [
                'name' => 'Module Expéditions & Ventes',
                'color' => '#4643A0',
                'gradient' => 'linear-gradient(135deg, #372E7D 0%, #4643A0 40%, #6C66BB 100%)',
                'icon' => 'mdi-ship-wheel',
                'route' => '/shippings',
                'route_label' => 'Module Ventes',
            ];
        }

        // 3. Production / Factory Module
        if (preg_match('/(production|usine|rendement|congel|yield|farine|guano)/ui', $text)) {
            return [
                'name' => 'Module Production & Usine',
                'color' => '#00897B',
                'gradient' => 'linear-gradient(135deg, #00695C 0%, #00897B 40%, #26A69A 100%)',
                'icon' => 'mdi-factory',
                'route' => '/production',
                'route_label' => 'Module Production',
            ];
        }

        // 4. Workforce / Fortnight Module
        if (preg_match('/(fortnight|quinzaine|ouvrier|workforce|section|corporal|caporal|pointage)/ui', $text)) {
            return [
                'name' => 'Module Quinzaines & Main-d’œuvre',
                'color' => '#1565C0',
                'gradient' => 'linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)',
                'icon' => 'mdi-account-group',
                'route' => '/fortnight',
                'route_label' => 'Module Quinzaines',
            ];
        }

        // 5. Warehouse / Stock Module
        if (preg_match('/(warehouse|entrepot|magasin|stock|inventaire|balance)/ui', $text)) {
            return [
                'name' => 'Module Entrepôts & Stocks',
                'color' => '#a21ee5',
                'gradient' => 'linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%)',
                'icon' => 'mdi-warehouse',
                'route' => '/warehouses',
                'route_label' => 'Module Entrepôts',
            ];
        }

        // 6. Supplier Invoice / Settlement Module
        if (preg_match('/(supplier_invoice|fournisseur.*facture|facture.*fournisseur|reglement.*fournisseur)/ui', $text)) {
            return [
                'name' => 'Factures & Règlements Fournisseurs',
                'color' => '#E65100',
                'gradient' => 'linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)',
                'icon' => 'mdi-receipt-text-outline',
                'route' => '/supplier_invoice',
                'route_label' => 'Factures Fournisseurs',
            ];
        }

        // 7. Client Invoice / Settlement Module
        if (preg_match('/(client_invoice|client.*facture|facture.*client|reglement.*client)/ui', $text)) {
            return [
                'name' => 'Factures & Règlements Clients',
                'color' => '#00838F',
                'gradient' => 'linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)',
                'icon' => 'mdi-file-document-outline',
                'route' => '/client_invoice',
                'route_label' => 'Factures Clients',
            ];
        }

        return [
            'name' => 'Jupiter ERP Intelligence',
            'color' => '#1565C0',
            'gradient' => 'linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)',
            'icon' => 'mdi-robot-happy-outline',
            'route' => '/charges',
            'route_label' => 'Tableau de Bord',
        ];
    }

    /**
     * Format a clean, lightweight string representation of the conversation history.
     */
    private function formatCleanConversationHistory(mixed $rawMessages): string
    {
        if (empty($rawMessages)) {
            return 'No prior conversation.';
        }

        $items = [];
        if (is_string($rawMessages)) {
            $decoded = json_decode($rawMessages, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = [['sender' => 'user', 'message' => $rawMessages]];
            }
        } elseif (is_array($rawMessages)) {
            $items = $rawMessages;
        }

        // Take up to the last 8 turns to avoid token overflow
        $recent = array_slice($items, -8);
        $lines = [];

        foreach ($recent as $turn) {
            $sender = ($turn['sender'] ?? 'user') === 'AI' || ($turn['role'] ?? '') === 'assistant' ? 'Assistant' : 'User';
            $msg = $turn['message'] ?? ($turn['text'] ?? ($turn['content'] ?? ''));
            if (!$msg) continue;

            // Strip HTML, script tags, style tags and excessive spaces
            $clean = strip_tags(preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $msg));
            $clean = preg_replace('/\s+/', ' ', trim($clean));
            if (mb_strlen($clean) > 280) {
                $clean = mb_substr($clean, 0, 280) . '...';
            }

            if (!empty($clean)) {
                $lines[] = "{$sender}: {$clean}";
            }
        }

        return !empty($lines) ? implode("\n", $lines) : 'No prior conversation.';
    }

    /**
     * Build the plain text prompt for regular/simple factual questions.
     */
    private function buildPlainTextPrompt(
        string $question,
        string $result,
        string $history,
        string $businessCtx,
        ?string $sqlQuery = null
    ): string {
        return <<<PROMPT
=== YOUR ROLE & IDENTITY ===
You are "Jupiter AI", the Elite Enterprise AI Data Analyst for Jupiter ERP.
The user asked a simple, regular factual question.
Deliver a direct, concise, perfectly formatted plain text answer (in clear Markdown).

=== RULES ===
1. Answer directly and precisely using the database result provided.
2. NO HTML tags, NO <script> tags, NO ApexCharts, NO massive card containers.
3. Use clean Markdown for emphasis: bold for numbers/amounts (e.g., **25 charge vouchers**, **147,769.50 MAD**).
4. Match the user's language (English, French, Spanish, Arabic).
5. If the count is 0 or empty, clearly state that no records were found for that period.
6. NO conversational filler like "Hello, as an AI...". Provide the direct answer first, then a short sentence with details if relevant.

=== CONVERSATION HISTORY ===
{$history}

=== USER QUESTION ===
{$question}

=== EXECUTED SQL QUERY ===
{$sqlQuery}

=== DATABASE DATA (EXACT TRUTH) ===
{$result}
PROMPT;
    }

    /**
     * Build the supercharged Vue.js 3 + ApexCharts + MDI prompt.
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
        string $excelContext = '',
        ?string $sqlQuery = null
    ): string {
        $mod = $this->resolveModuleDesign($question, $sqlQuery ?? '');
        $modName = $mod['name'];
        $modCol = $mod['color'];
        $modGrad = $mod['gradient'];
        $modIco = $mod['icon'];
        $modRoute = $mod['route'];
        $modRouteLbl = $mod['route_label'];

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

=== USER QUESTION ===
{$question}

=== DATABASE DATA (ABSOLUTE SOURCE OF TRUTH - NEVER INVENT NUMBERS) ===
{$result}

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

4. CRITICAL LANGUAGE RULE:
   Detect the language of the question (English, French, Spanish, Arabic) and write all labels, explanations, headers, table columns, and tooltips in that EXACT same language!

5. MANDATORY 6-SECTION DASHBOARD STRUCTURE:
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
      Analyse Validée • IA Core
    </div>
  </div>

  <!-- SECTION 2: DIRECT SYNTHESIS HIGHLIGHT BANNER -->
  <div style="background: {$innerBg}; border-left: 4px solid {$modCol}; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 13.5px; line-height: 1.6; color: {$textColor}; border: 1px solid {$innerBorder};">
    <div style="display: flex; align-items: flex-start; gap: 10px;">
      <i class="v-icon notranslate mdi mdi-information-outline" style="font-size: 18px; color: {$modCol}; margin-top: 2px; flex-shrink: 0;"></i>
      <div>[Write a direct, crystal-clear 1-2 sentence executive answer to the user question using actual totals from the database data]</div>
    </div>
  </div>

  <!-- SECTION 3: RESPONSIVE KPI METRIC CARDS (CSS Grid: 2 to 4 cards) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- Card 1 -->
    <div class="ai-analytics-kpi" style="background: {$innerBg}; border-radius: 14px; padding: 16px 18px; border: 1px solid {$innerBorder}; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: {$modCol}18; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="v-icon notranslate mdi mdi-cash-multiple" style="font-size: 22px; color: {$modCol};"></i>
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 700; color: {$subColor}; text-transform: uppercase; letter-spacing: 0.5px;">TOTAL MONTANT</div>
        <div style="font-size: 20px; font-weight: 800; color: {$modCol}; margin-top: 2px;">[Formatted Value e.g. 147 769 289.55 MAD]</div>
      </div>
    </div>
    <!-- Card 2 -->
    <div class="ai-analytics-kpi" style="background: {$innerBg}; border-radius: 14px; padding: 16px 18px; border: 1px solid {$innerBorder}; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: #00897B18; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="v-icon notranslate mdi mdi-scale" style="font-size: 22px; color: #00897B;"></i>
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 700; color: {$subColor}; text-transform: uppercase; letter-spacing: 0.5px;">POIDS TOTAL ou ÉLÉMENT TOP</div>
        <div style="font-size: 20px; font-weight: 800; color: #00897B; margin-top: 2px;">[Formatted Value]</div>
      </div>
    </div>
  </div>

  <!-- SECTION 4: DETAILED DATA TABLE (MANDATORY: MUST USE <table>, <thead>, <tbody>, <tr>, <th>, <td>) -->
  <div class="ai-analytics-inner" style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid {$innerBorder};">
    <div style="font-size: 13px; font-weight: 800; color: {$modCol}; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <i class="v-icon notranslate mdi mdi-table" style="font-size: 18px;"></i>
      DÉTAILS DES ENREGISTREMENTS
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
      Visualisation Graphique
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
    Données Certifiées ERP • Source Unique de Vérité
  </div>
</div>

<!-- SCRIPT BLOCK: SELF-EXECUTING VANILLA JAVASCRIPT APEXCHARTS (NEVER USE createApp OR Vue MOUNT) -->
<script>
(function(){
  var chartEl = document.querySelector('#aichart_{$id}');
  if (!chartEl || !window.ApexCharts) return;
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
  var chart = new ApexCharts(chartEl, options);
  chart.render();
  window.__ai_charts = window.__ai_charts || {};
  window.__ai_charts['aichart_{$id}'] = chart;
})();
</script>
PROMPT;
    }

    /**
     * Post-process and sanitize AI response to guarantee premium UI/UX.
     */
    private function beautifyAndValidateAIAnswer(string $answer, string $question, array $resultRows, int|string $id, string $theme): string
    {
        // 1. Strip markdown fences if present
        $answer = preg_replace('/^```(?:html|vue|xml)?\s*/i', '', trim($answer));
        $answer = preg_replace('/\s*```$/', '', $answer);

        // 2. Fix broken MDI icon classes: <i class="mdi-xyz"> -> <i class="v-icon notranslate mdi mdi-xyz">
        $answer = preg_replace_callback('/<i\s+class="([^"]*)"/i', function ($m) {
            $cls = $m[1];
            if (str_contains($cls, 'mdi-') && !str_contains($cls, 'mdi ')) {
                $cls = 'v-icon notranslate mdi ' . $cls;
            }
            return '<i class="v-icon notranslate ' . trim($cls) . '"';
        }, $answer);

        // 3. Ensure any table has class="ai-analytics-table"
        if (str_contains($answer, '<table') && !str_contains($answer, 'ai-analytics-table')) {
            $answer = preg_replace('/<table(\s+class=")?/i', '<table class="ai-analytics-table ', $answer, 1);
        }

        // 4. Ensure table is wrapped in responsive scroll container
        if (str_contains($answer, '<table') && !str_contains($answer, 'overflow-x: auto')) {
            $answer = preg_replace('/(<table[\s\S]*?<\/table>)/i', '<div style="overflow-x: auto; width: 100%;">$1</div>', $answer);
        }

        // 5. Replace any Vue createApp script with safe Vanilla JS ApexCharts execution
        if (str_contains($answer, 'createApp(') && str_contains($answer, 'ApexCharts')) {
            $answer = preg_replace_callback('/<script>([\s\S]*?)<\/script>/i', function ($m) {
                $js = $m[1];
                if (str_contains($js, 'new ApexCharts')) {
                    return "<script>\n(function(){\n  try {\n    " . $js . "\n  } catch(e) { console.warn('AI Chart fallback error:', e); }\n})();\n</script>";
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

        // If SQL query returned multiple data rows (e.g. breakdown by category, month, section):
        if (count($resultRows) >= 2) {
            $hasTable = str_contains($answer, '<table') && str_contains($answer, '<tr') && str_contains($answer, '<td');
            $hasChart = str_contains($answer, 'aichart_');

            // If it has NEITHER a valid table NOR a chart, it's degraded output
            if (!$hasTable && !$hasChart) {
                return false;
            }

            // Detect raw unformatted smashed text like "Catégorie Prix total (MAD) Du frais..." without table tags
            if (preg_match('/(catégorie|category|mois|month).*(total|prix|weight)/ui', $answer) && !str_contains($answer, '<table')) {
                return false;
            }
        }

        return true;
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
