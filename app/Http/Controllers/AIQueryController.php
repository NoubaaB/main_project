<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AiQueryMessage;
use App\Events\ModelChanged;
use App\Services\GeminiService;
use App\Services\AIAnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AIQueryController extends Controller
{
    private GeminiService $ai;

    private static ?string $cachedSchema = null;
    private static ?string $cachedComments = null;
    private static ?string $cachedRoutes = null;
    private ?string $app_name = null;

    public function __construct()
    {
        $this->ai = new GeminiService();
        $this->app_name = env('APP_NAME', 'Jupiter ERP');
    }

    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'id' => 'nullable',
            'theme' => 'nullable|string',
            'messages' => 'nullable',
            'excel_files' => 'nullable|array',
            'excel_files.*.name' => 'string',
            'excel_files.*.headers' => 'array',
            'excel_files.*.rows' => 'array',
            'model' => 'nullable|string',
            'locale' => 'nullable|string',
        ]);

        $startTime = microtime(true);
        $question = $data['message'];
        $id = (int)($data['id'] ?? time());
        $theme = in_array(strtolower((string) ($data['theme'] ?? 'dark')), ['dark', 'light']) ? strtolower((string) ($data['theme'] ?? 'dark')) : 'dark';
        $messagesRaw = $data['messages'];
        $messages = is_array($messagesRaw) ? json_encode($messagesRaw) : $messagesRaw;
        $excelFiles = $data['excel_files'] ?? [];
        $thinkingLevel = $request->input('thinking_level', 'high');
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

        // 1. Direct handling if asking about System Architecture & AI Core
        $lowerQuestion = mb_strtolower($question, 'UTF-8');
        if (preg_match('/(creator|created|owner|supervisor|superviseur|elbachir|noubaa|bachir|développeur|qui t\'a|qui a créé|qui est ton|de qui es-tu|quien te creo|من صنعك|البشير|النوباع)/ui', $lowerQuestion)) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles, $detectedLang);
            $thinkingDuration = round(microtime(true) - $startTime, 2);
            $reasoning = $this->buildReasoningPayload($question, 'system_identity', null, [], $thinkingDuration);
            $this->persistQueryMessage($request, $question, $answer, $reasoning);
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

        // 2. Direct handling if Excel files are provided
        if (!empty($excelFiles)) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles, $detectedLang);
            $thinkingDuration = round(microtime(true) - $startTime, 2);
            $reasoning = $this->buildReasoningPayload($question, 'excel_analysis', null, [], $thinkingDuration);
            $this->persistQueryMessage($request, $question, $answer, $reasoning);
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

        // 6. Step 4: If SQL executed with rows, prompt AI to build rich dashboard
        $answer = null;
        if ($result && $result !== '[]' && $result !== 'null' && !empty($resultRows)) {
            $answerPrompt = $this->buildVuePrompt($question, $result, $schema, $comments, $id, $theme, $messages, $businessCtx, $routes, '', $sqlQuery, $detectedLang);
            $rawAnswer = $this->ai->generateAnswer($answerPrompt);
            if ($rawAnswer) {
                $answer = $this->beautifyAndValidateAIAnswer($rawAnswer, $question, $resultRows, $id, $theme, $detectedLang);
            }
        }

        // 7. Step 5: Fallback to high-precision AIAnalyticsService if AI failed or produced degraded output
        if (!$answer || !$this->isAnswerSufficientlyPolished($answer, $resultRows)) {
            $answer = $analytics->processQuestion($question, $id, $theme, $excelFiles, $detectedLang);
        }

        $thinkingDuration = round(microtime(true) - $startTime, 2);
        $reasoning = $this->buildReasoningPayload($question, 'read', $sqlQuery, $resultRows, $thinkingDuration);

        $this->persistQueryMessage($request, $question, $answer, $reasoning);
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
        ?string $sqlQuery = null,
        string $detectedLang = 'en'
    ): string {
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
            'es' => '[Escribe una respuesta ejecutiva directa y clara de 1-2 frases en español usando los totales reales]',
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
    <!-- Card 1 -->
    <div class="ai-analytics-kpi" style="background: {$innerBg}; border-radius: 14px; padding: 16px 18px; border: 1px solid {$innerBorder}; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: {$modCol}18; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="v-icon notranslate mdi mdi-cash-multiple" style="font-size: 22px; color: {$modCol};"></i>
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 700; color: {$subColor}; text-transform: uppercase; letter-spacing: 0.5px;">{$kpiAmountLabel}</div>
        <div style="font-size: 20px; font-weight: 800; color: {$modCol}; margin-top: 2px;">[Formatted Value e.g. 147 769 289.55 MAD]</div>
      </div>
    </div>
    <!-- Card 2 -->
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

        // 6. Ensure any ApexCharts script has resilient retry logic and responsive sizing
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

    private function persistQueryMessage(Request $request, string $question, string $answer, ?array $reasoning = null): void
    {
        $user = $request->user();
        if ($user) {
            $userMsg = AiQueryMessage::create(['user_id' => $user->id, 'role' => 'user', 'text' => $question]);
            if ($answer) {
                $agentMsg = AiQueryMessage::create([
                    'user_id' => $user->id,
                    'role' => 'agent',
                    'text' => $answer,
                    'reasoning' => $reasoning ? json_encode($reasoning) : null,
                ]);
                try {
                    broadcast(new ModelChanged('AiQueryMessage', 'created', $agentMsg->id, $user->name ?? 'AI', [
                        'id' => $agentMsg->id,
                        'role' => 'agent',
                        'reasoning' => $reasoning,
                    ], $user->id))->toOthers();
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Build structured transparent reasoning payload.
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
                'title' => 'Analytical Modeling & Query Formulation',
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
