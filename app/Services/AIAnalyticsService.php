<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AIAnalyticsService
{
    private string $appName;
    private string $creatorName = "ELBACHIR NOUBAA";

    public function __construct()
    {
        $this->appName = env('APP_NAME', 'Jupiter ERP');
    }

    /**
     * Main entry point to process a question and generate a rich Vue 3 + Vuetify + ApexCharts response.
     */
    public function processQuestion(string $question, int|string $id, string $theme = 'dark', array $excelFiles = []): string
    {
        $uniqueId = 'ai_' . $id . '_' . substr(md5($question . microtime()), 0, 6);
        $chartId = 'aichart_' . $id . '_' . substr(md5($question . microtime()), 0, 6);
        $lang = $this->detectLanguage($question);

        // 1. Check if user is asking about the creator, supervisor, owner, or system identity
        if ($this->isCreatorOrIdentityQuestion($question)) {
            return $this->generateCreatorResponse($uniqueId, $lang, $theme);
        }

        // 2. Check if Excel files are uploaded and question asks about them
        if (!empty($excelFiles)) {
            return $this->generateExcelAnalysisResponse($uniqueId, $chartId, $question, $excelFiles, $lang, $theme);
        }

        // 3. Classify business domain
        $domain = $this->classifyDomain($question);

        switch ($domain) {
            case 'charges':
                return $this->generateChargesAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'shippings':
                return $this->generateShippingsAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'productions':
                return $this->generateProductionsAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'warehouses':
                return $this->generateWarehousesAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'finances':
                return $this->generateFinancesAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'workforce':
                return $this->generateWorkforceAnalysis($uniqueId, $chartId, $question, $lang, $theme);
            case 'overview':
            default:
                return $this->generateOverviewAnalysis($uniqueId, $chartId, $question, $lang, $theme);
        }
    }

    /**
     * Detect user's language: 'fr', 'es', 'ar', or 'en'.
     */
    public function detectLanguage(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        // Arabic check
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $lower)) {
            return 'ar';
        }

        // French signals
        if (preg_match('/\b(qui|est|sont|les|des|du|dans|pour|avec|donne|moi|affiche|combien|achats|charges|ventes|expéditions|production|stock|magasin|entrepôt|main|oeuvre|ouvrier|facture|règlement|créance|dette|résumé|global|aujourd|hier|mois|année|rendement|poisson|espece|fournisseur|client|prix|total|poids)\b/ui', $lower)) {
            return 'fr';
        }

        // Spanish signals
        if (preg_match('/\b(quien|es|son|los|las|para|con|dame|muestra|cuanto|compras|ventas|produccion|almacen|pescado|proveedor|cliente|factura|total|peso|precio|este|mes|ano|hoy|ayer)\b/ui', $lower)) {
            return 'es';
        }

        return 'en';
    }

    /**
     * Check if question is about creator / supervisor / owner / identity.
     */
    private function isCreatorOrIdentityQuestion(string $text): bool
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $keywords = [
            'creator', 'created you', 'who created', 'who made you', 'owner', 'who owns', 'supervisor', 'superviseur',
            'who is elbachir', 'elbachir', 'noubaa', 'bachir', 'développeur', 'qui t\'a créé', 'qui a créé',
            'qui est ton créateur', 'qui est ton propriétaire', 'qui est ton superviseur', 'qui es tu', 'qui es-tu',
            'de qui es-tu', 'qui a fait cette application', 'quien te creo', 'quien es tu creador', 'quien es elbachir',
            'من صنعك', 'من طورك', 'من هو صاحب', 'البشير', 'النوباع'
        ];

        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Classify domain into charges, shippings, productions, warehouses, finances, workforce, overview.
     */
    private function classifyDomain(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        if (preg_match('/\b(charge|charges|achat|achats|fournisseur|fournisseurs|reception|receptions|navire|navires|bateau|bateaux|camion|camions|compras|proveedor|suppliers?|purchases?)\b/ui', $lower)) {
            return 'charges';
        }

        if (preg_match('/\b(shipping|shippings|vente|ventes|expedition|expéditions|client|clients|destination|export|exportation|ventas|sales?)\b/ui', $lower)) {
            return 'shippings';
        }

        if (preg_match('/\b(production|productions|rendement|congelation|congélation|frais|guano|rsw|usine|farine|huile|produit|produits|yield|processing)\b/ui', $lower)) {
            return 'productions';
        }

        if (preg_match('/\b(stock|stocks|warehouse|warehouses|magasin|magasins|entrepot|entrepôt|entrepôts|chambre|chambres|inventaire|inventaires|balance|almacen|almacenes|inventory)\b/ui', $lower)) {
            return 'warehouses';
        }

        if (preg_match('/\b(facture|factures|invoices?|reglement|règlement|règlements|paiement|paiements|payments?|creance|créance|créances|dette|dettes|solde|soldes|impaye|impayés|chiffre|tresorerie|trésorerie|finances?)\b/ui', $lower)) {
            return 'finances';
        }

        if (preg_match('/\b(workforce|workforces|personnel|ouvrier|ouvriers|main|d\'oeuvre|salarié|salariés|quinzaine|fortnight|activite|activité|activités|section|sections|caporal|caporaux|paie|salaire)\b/ui', $lower)) {
            return 'workforce';
        }

        return 'overview';
    }

    /**
     * Generates Creator & Supervisor response honoring ELBACHIR NOUBAA.
     */
    public function generateCreatorResponse(string $uniqueId, string $lang, string $theme): string
    {
        $titles = [
            'fr' => [
                'role' => 'Créateur & Superviseur Visionnaire',
                'name' => 'ELBACHIR NOUBAA',
                'badge' => 'Propriétaire & Concepteur en Chef',
                'bio' => 'L\'agent intelligent AI et l\'ensemble de l\'ERP Jupiter sont créés, conçus et entièrement supervisés par <strong>ELBACHIR NOUBAA</strong>. Expert en architecture logicielle industrielle et gestion halieutique, M. NOUBAA a conçu ce système pour assurer une traçabilité intégrale, un contrôle financier rigoureux et une intelligence analytique de pointe.',
                'highlights_title' => 'Piliers d\'Excellence sous sa supervision',
                'pillar1' => 'Traçabilité Halieutique & Temps Réel (Achats, Usine, Stocks, Expéditions)',
                'pillar2' => 'Intelligence Artificielle Connectée & Modélisation Financière Avancée',
                'pillar3' => 'Pilotage de la Performance & Optimisation du Rendement Industriel',
                'footer' => 'Système certifié et piloté sous l\'autorité d\'ELBACHIR NOUBAA'
            ],
            'es' => [
                'role' => 'Creador y Supervisor Visionario',
                'name' => 'ELBACHIR NOUBAA',
                'badge' => 'Propietario y Diseñador Principal',
                'bio' => 'El agente inteligente de IA y todo el ERP Jupiter fueron creados, diseñados y están completamente supervisados por <strong>ELBACHIR NOUBAA</strong>. Como líder y arquitecto de soluciones industriales y pesqueras, el Sr. NOUBAA diseñó este sistema para garantizar trazabilidad total, análisis financiero exacto e inteligencia predictiva.',
                'highlights_title' => 'Pilares de Excelencia bajo su supervisión',
                'pillar1' => 'Trazabilidad Pesquera en Tiempo Real (Compras, Planta, Stock, Envíos)',
                'pillar2' => 'Inteligencia Artificial Integrada y Control Financiero Dinámico',
                'pillar3' => 'Gestión Industrial de Alto Rendimiento y Seguridad de Datos',
                'footer' => 'Sistema certificado y dirigido bajo la autoridad de ELBACHIR NOUBAA'
            ],
            'ar' => [
                'role' => 'المبتكر والمشرف العام',
                'name' => 'البشير النوباع (ELBACHIR NOUBAA)',
                'badge' => 'المالك والمصمم الرئيسي',
                'bio' => 'تم إنشاء وتصميم هذا المساعد الذكي ونظام جوبيتر (Jupiter ERP) بالكامل تحت إشراف وقيادة <strong>البشير النوباع (ELBACHIR NOUBAA)</strong>، المبتكر والمشرف المباشر على هذا النظام الرقمي المتطور لإدارة وتتبع منتجات الصيد البحري والتحليل المالي الدقيق.',
                'highlights_title' => 'ركائز التميز تحت إشرافه',
                'pillar1' => 'تتبع شامل في الوقت الفعلي (المشتريات، الإنتاج، المخازن، الشحن)',
                'pillar2' => 'ذكاء اصطناعي متصل بقواعد البيانات وتحليلات بيانية فورية',
                'pillar3' => 'تحسين المردودية الصناعية والمراقبة المالية الصارمة',
                'footer' => 'نظام معتمد ومطور برؤية وتوجيه البشير النوباع'
            ],
            'en' => [
                'role' => 'Creator & Lead Supervisor',
                'name' => 'ELBACHIR NOUBAA',
                'badge' => 'Owner & Chief Architect',
                'bio' => 'This AI Agent and the entire Jupiter ERP ecosystem are created, engineered, and supervised by <strong>ELBACHIR NOUBAA</strong>. As the visionary architect of this industrial fishery intelligence platform, Mr. NOUBAA designed every facet to deliver absolute traceability, financial precision, and real-time operational intelligence.',
                'highlights_title' => 'Pillars of Excellence under his Supervision',
                'pillar1' => 'Full Fishery Traceability & Real-Time Sync (Charges, Plant, Cold Stores, Shipping)',
                'pillar2' => 'Direct Database Intelligence & High-Performance Visual Analytics',
                'pillar3' => 'Industrial Yield Maximization & Enterprise Financial Integrity',
                'footer' => 'System engineered and supervised under the authority of ELBACHIR NOUBAA'
            ]
        ];

        $t = $titles[$lang] ?? $titles['en'];
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 24px; border: 1px solid rgba(66, 165, 245, 0.3); box-shadow: 0 10px 30px rgba(21, 101, 192, 0.15); margin-bottom: 8px;">
    <!-- Creator Badge Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid rgba(66, 165, 245, 0.2); padding-bottom: 16px;">
      <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 52px; height: 52px; border-radius: 16px; background: linear-gradient(135deg, #1565C0 0%, #1E88E5 50%, #42A5F5 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 16px rgba(21, 101, 192, 0.4);">
          <i class="v-icon notranslate mdi mdi-crown text-white" style="font-size: 28px;"></i>
        </div>
        <div>
          <div style="font-size: 20px; font-weight: 800; color: #42A5F5; letter-spacing: 0.5px;">{$t['name']}</div>
          <div style="font-size: 13px; font-weight: 600; color: {$subColor}; display: flex; align-items: center; gap: 6px;">
            <i class="v-icon notranslate mdi mdi-shield-check text-blue" style="font-size: 16px; color: #42A5F5 !important;"></i>
            <span>{$t['role']}</span>
          </div>
        </div>
      </div>
      <span style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.35); padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
        {$t['badge']}
      </span>
    </div>

    <!-- Creator Description -->
    <div style="font-size: 14.5px; line-height: 1.8; color: {$textColor}; margin-bottom: 20px;">
      {$t['bio']}
    </div>

    <!-- Pillars Card -->
    <div style="background: rgba(66, 165, 245, 0.06); border-radius: 14px; padding: 16px; border: 1px dashed rgba(66, 165, 245, 0.35); margin-bottom: 16px;">
      <div style="font-size: 12px; font-weight: 800; color: #42A5F5; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;">
        <i class="v-icon notranslate mdi mdi-star-circle text-blue" style="font-size: 14px; margin-right: 6px;"></i>
        {$t['highlights_title']}
      </div>
      <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: {$textColor};">
        <div style="display: flex; align-items: center; gap: 8px;">
          <i class="v-icon notranslate mdi mdi-check-circle" style="font-size: 15px; color: #00E676;"></i>
          <span>{$t['pillar1']}</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <i class="v-icon notranslate mdi mdi-check-circle" style="font-size: 15px; color: #00E676;"></i>
          <span>{$t['pillar2']}</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <i class="v-icon notranslate mdi mdi-check-circle" style="font-size: 15px; color: #00E676;"></i>
          <span>{$t['pillar3']}</span>
        </div>
      </div>
    </div>

    <!-- Action / Verification Pill -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 11.5px; color: {$subColor}; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 14px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <i class="v-icon notranslate mdi mdi-check-decagram" style="font-size: 16px; color: #42A5F5;"></i>
        <span>{$t['footer']}</span>
      </div>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: linear-gradient(135deg, #1565C0, #42A5F5); color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/dashboard">
        <i class="v-icon notranslate mdi mdi-view-dashboard" style="font-size: 14px; margin-right: 6px;"></i>
        Dashboard
      </button>
    </div>
  </div>
</div>
HTML;
    }

    /**
     * Generates Executive Overview / Global KPI Deck with ApexCharts.
     */
    public function generateOverviewAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $chargesCount = DB::table('charges')->whereNull('deleted_at')->count();
        $chargesWeight = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_weight');
        $chargesPrice = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_price');

        $shippingsCount = DB::table('shippings')->whereNull('deleted_at')->count();
        $shippingsWeight = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_weight');
        $shippingsPrice = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_price');

        $prodCount = DB::table('productions')->whereNull('deleted_at')->count();
        $prodFrozenWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('frozen_production_weight');
        $prodGuanoWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('guano_production_weight');

        $warehousesCount = DB::table('warehouses')->whereNull('deleted_at')->count();
        $totalStockKg = (float) DB::table('warehouses')->whereNull('deleted_at')->sum('total_weight');

        $workforceCount = DB::table('workforces')->whereNull('deleted_at')->count();
        $activitiesCount = DB::table('fortnight_activities')->whereNull('deleted_at')->count();

        $labels = [
            'fr' => [
                'title' => 'Tableau de Bord Exécutif & Indicateurs Clés (KPI)',
                'badge' => 'Synthèse Globale ERP',
                'p_purchases' => 'Achats (Charges)',
                'p_sales' => 'Ventes (Expéditions)',
                'p_prod' => 'Productions Usine',
                'p_stock' => 'Stock en Magasins',
                'p_workforce' => 'Main d\'œuvre',
                'p_chart' => 'Répartition des Volumes Clés (Tonnes)',
                'insight_title' => 'Analyses et Éclairages Opérationnels',
                'insight1' => sprintf("Total des réceptions enregistrées: %s lots pour un volume de %s T et une valeur de %s MAD.", number_format($chargesCount, 0, ',', ' '), number_format($chargesWeight / 1000, 2, ',', ' '), number_format($chargesPrice, 2, ',', ' ')),
                'insight2' => sprintf("Expéditions et ventes globales: %s opérations totalisant %s T de poisson expédié valorisé à %s MAD.", number_format($shippingsCount, 0, ',', ' '), number_format($shippingsWeight / 1000, 2, ',', ' '), number_format($shippingsPrice, 2, ',', ' ')),
                'insight3' => sprintf("Stock disponible actuellement sous froid: %s T réparties dans %s entrepôts.", number_format($totalStockKg / 1000, 2, ',', ' '), $warehousesCount),
                'chart_purchases' => 'Achats Réceptionnés',
                'chart_sales' => 'Ventes Expédiées',
                'chart_frozen' => 'Congélation Finie',
                'chart_guano' => 'Farine & Guano',
                'chart_stock' => 'Stock Actuel'
            ],
            'es' => [
                'title' => 'Panel Ejecutivo e Indicadores Clave (KPI)',
                'badge' => 'Resumen Global ERP',
                'p_purchases' => 'Compras (Cargas)',
                'p_sales' => 'Ventas (Envíos)',
                'p_prod' => 'Producción Planta',
                'p_stock' => 'Stock en Almacenes',
                'p_workforce' => 'Mano de Obra',
                'p_chart' => 'Distribución de Volúmenes Clave (Toneladas)',
                'insight_title' => 'Análisis y Observaciones Operativas',
                'insight1' => sprintf("Total de recepciones registradas: %s lotes con un volumen de %s T y un valor de %s MAD.", number_format($chargesCount, 0, ',', ' '), number_format($chargesWeight / 1000, 2, ',', ' '), number_format($chargesPrice, 2, ',', ' ')),
                'insight2' => sprintf("Envíos y ventas globales: %s operaciones totalizando %s T enviadas valoradas en %s MAD.", number_format($shippingsCount, 0, ',', ' '), number_format($shippingsWeight / 1000, 2, ',', ' '), number_format($shippingsPrice, 2, ',', ' ')),
                'insight3' => sprintf("Stock actualmente bajo frío: %s T distribuidas en %s almacenes.", number_format($totalStockKg / 1000, 2, ',', ' '), $warehousesCount),
                'chart_purchases' => 'Compras Recibidas',
                'chart_sales' => 'Ventas Enviadas',
                'chart_frozen' => 'Congelado Terminado',
                'chart_guano' => 'Harina y Guano',
                'chart_stock' => 'Stock Actual'
            ],
            'ar' => [
                'title' => 'لوحة القيادة التنفيذية والمؤشرات الرئيسية (KPI)',
                'badge' => 'ملخص شامل لنظام جوبيتر',
                'p_purchases' => 'المشتريات (الشحنات)',
                'p_sales' => 'المبيعات (الشحن)',
                'p_prod' => 'الإنتاج الصناعي',
                'p_stock' => 'المخزون الحالي',
                'p_workforce' => 'اليد العاملة',
                'p_chart' => 'توزيع الأحجام الرئيسية (بالأطنان)',
                'insight_title' => 'تحليلات ورؤى تشغيلية',
                'insight1' => sprintf("إجمالي الشحنات المستلمة: %s شحنة بحجم %s طن وقيمة %s درهم مغربي.", number_format($chargesCount, 0, ',', ' '), number_format($chargesWeight / 1000, 2, ',', ' '), number_format($chargesPrice, 2, ',', ' ')),
                'insight2' => sprintf("المبيعات وعمليات التصدير: %s عملية بحجم %s طن من الأسماك بقيمة %s درهم مغربي.", number_format($shippingsCount, 0, ',', ' '), number_format($shippingsWeight / 1000, 2, ',', ' '), number_format($shippingsPrice, 2, ',', ' ')),
                'insight3' => sprintf("المخزون المتوفر بغرف التبريد: %s طن موزع على %s مخازن.", number_format($totalStockKg / 1000, 2, ',', ' '), $warehousesCount),
                'chart_purchases' => 'المشتريات المستلمة',
                'chart_sales' => 'المبيعات المشحونة',
                'chart_frozen' => 'الأسماك المجمدة',
                'chart_guano' => 'دقيق السمك (جوانو)',
                'chart_stock' => 'المخزون الحالي'
            ],
            'en' => [
                'title' => 'Executive Dashboard & Key Indicators (KPI)',
                'badge' => 'Global ERP Summary',
                'p_purchases' => 'Purchases (Charges)',
                'p_sales' => 'Sales (Shippings)',
                'p_prod' => 'Factory Production',
                'p_stock' => 'Cold Storage Stock',
                'p_workforce' => 'Workforce & Shifts',
                'p_chart' => 'Key Volume Distribution (Metric Tons)',
                'insight_title' => 'Operational Analytics & Insights',
                'insight1' => sprintf("Total reception lots: %s lots with volume of %s Tons valued at %s MAD.", number_format($chargesCount, 0, ',', ' '), number_format($chargesWeight / 1000, 2, ',', ' '), number_format($chargesPrice, 2, ',', ' ')),
                'insight2' => sprintf("Outbound sales and shipments: %s shipments totaling %s Tons valued at %s MAD.", number_format($shippingsCount, 0, ',', ' '), number_format($shippingsWeight / 1000, 2, ',', ' '), number_format($shippingsPrice, 2, ',', ' ')),
                'insight3' => sprintf("Current inventory in cold storage: %s Tons across %s warehouses.", number_format($totalStockKg / 1000, 2, ',', ' '), $warehousesCount),
                'chart_purchases' => 'Purchases Received',
                'chart_sales' => 'Sales Shipped',
                'chart_frozen' => 'Frozen Finished',
                'chart_guano' => 'Fishmeal & Guano',
                'chart_stock' => 'Current Stock'
            ]
        ];

        $t = $labels[$lang] ?? $labels['en'];
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        // Prepare chart data in Tons
        $cSeries = [
            round($chargesWeight / 1000, 2),
            round($shippingsWeight / 1000, 2),
            round($prodFrozenWeight / 1000, 2),
            round($prodGuanoWeight / 1000, 2),
            round($totalStockKg / 1000, 2),
        ];
        $cCategories = [
            $t['chart_purchases'],
            $t['chart_sales'],
            $t['chart_frozen'],
            $t['chart_guano'],
            $t['chart_stock']
        ];

        $seriesJson = json_encode($cSeries);
        $categoriesJson = json_encode($cCategories);

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(66, 165, 245, 0.25); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(66, 165, 245, 0.15); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #1565C0, #42A5F5); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.35);">
          <i class="v-icon notranslate mdi mdi-view-dashboard-outline text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #42A5F5;">{$t['title']}</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">
            Supervised & Designed by <strong style="color: #42A5F5;">ELBACHIR NOUBAA</strong>
          </div>
        </div>
      </div>
      <span style="background: rgba(66, 165, 245, 0.12); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.3); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        {$t['badge']}
      </span>
    </div>

    <!-- 4 Key Stat Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <!-- Purchases Stat -->
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(21, 101, 192, 0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
          <span style="font-size: 12px; font-weight: 700; color: #42A5F5;">{$t['p_purchases']}</span>
          <i class="v-icon notranslate mdi mdi-truck-delivery" style="font-size: 18px; color: #42A5F5;"></i>
        </div>
        <div style="font-size: 18px; font-weight: 800; color: {$textColor}; margin-bottom: 2px;">
          {$this->formatNumber($chargesWeight, 2)} <span style="font-size: 12px; color: {$subColor};">KG</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #00E676;">
          {$this->formatNumber($chargesPrice, 2)} MAD
        </div>
        <div style="font-size: 11px; color: {$subColor}; margin-top: 4px;">
          {$chargesCount} réceptions enregistrées
        </div>
      </div>

      <!-- Sales Stat -->
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(108, 102, 187, 0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
          <span style="font-size: 12px; font-weight: 700; color: #6c66bb;">{$t['p_sales']}</span>
          <i class="v-icon notranslate mdi mdi-ship-wheel" style="font-size: 18px; color: #6c66bb;"></i>
        </div>
        <div style="font-size: 18px; font-weight: 800; color: {$textColor}; margin-bottom: 2px;">
          {$this->formatNumber($shippingsWeight, 2)} <span style="font-size: 12px; color: {$subColor};">KG</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #00E676;">
          {$this->formatNumber($shippingsPrice, 2)} MAD
        </div>
        <div style="font-size: 11px; color: {$subColor}; margin-top: 4px;">
          {$shippingsCount} expéditions clients
        </div>
      </div>

      <!-- Stock Stat -->
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(162, 30, 229, 0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
          <span style="font-size: 12px; font-weight: 700; color: #a21ee5;">{$t['p_stock']}</span>
          <i class="v-icon notranslate mdi mdi-warehouse" style="font-size: 18px; color: #a21ee5;"></i>
        </div>
        <div style="font-size: 18px; font-weight: 800; color: {$textColor}; margin-bottom: 2px;">
          {$this->formatNumber($totalStockKg, 2)} <span style="font-size: 12px; color: {$subColor};">KG</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #42A5F5;">
          {$warehousesCount} Magasins actifs
        </div>
        <div style="font-size: 11px; color: {$subColor}; margin-top: 4px;">
          Sous surveillance thermique
        </div>
      </div>

      <!-- Production Stat -->
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 137, 123, 0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
          <span style="font-size: 12px; font-weight: 700; color: #00897B;">{$t['p_prod']}</span>
          <i class="v-icon notranslate mdi mdi-factory" style="font-size: 18px; color: #00897B;"></i>
        </div>
        <div style="font-size: 18px; font-weight: 800; color: {$textColor}; margin-bottom: 2px;">
          {$this->formatNumber($prodFrozenWeight, 2)} <span style="font-size: 12px; color: {$subColor};">KG Congelé</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #FFA000;">
          {$this->formatNumber($prodGuanoWeight, 2)} KG Guano
        </div>
        <div style="font-size: 11px; color: {$subColor}; margin-top: 4px;">
          {$prodCount} sessions d'usine
        </div>
      </div>
    </div>

    <!-- Chart Container -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 18px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 13px; font-weight: 800; color: #42A5F5; text-transform: uppercase; letter-spacing: 0.5px;">
          <i class="v-icon notranslate mdi mdi-chart-bar" style="font-size: 16px; margin-right: 6px;"></i>
          {$t['p_chart']}
        </span>
        <span style="font-size: 11px; color: {$subColor};">Unité: Tonnes métriques</span>
      </div>
      <div id="{$chartId}" style="min-height: 280px; width: 100%;"></div>
    </div>

    <!-- Analytical Insights -->
    <div style="background: rgba(66, 165, 245, 0.05); border-radius: 14px; padding: 16px; border-left: 4px solid #42A5F5; margin-bottom: 16px;">
      <div style="font-size: 12px; font-weight: 800; color: #42A5F5; text-transform: uppercase; margin-bottom: 8px;">
        <i class="v-icon notranslate mdi mdi-lightbulb-on" style="font-size: 15px; margin-right: 6px;"></i>
        {$t['insight_title']}
      </div>
      <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.7; color: {$textColor};">
        <li>{$t['insight1']}</li>
        <li>{$t['insight2']}</li>
        <li>{$t['insight3']}</li>
      </ul>
    </div>

    <!-- Navigation Quick Links -->
    <div style="display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #42A5F5; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/charges">
        <i class="v-icon notranslate mdi mdi-truck-delivery" style="font-size: 14px; margin-right: 4px;"></i>
        Charges
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #4643a0; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/shippings">
        <i class="v-icon notranslate mdi mdi-ship-wheel" style="font-size: 14px; margin-right: 4px;"></i>
        Shippings
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #00897B; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/production/kanban">
        <i class="v-icon notranslate mdi mdi-factory" style="font-size: 14px; margin-right: 4px;"></i>
        Production
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #a21ee5; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/warehouses">
        <i class="v-icon notranslate mdi mdi-warehouse" style="font-size: 14px; margin-right: 4px;"></i>
        Warehouses
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: [{
        name: "Volume (T)",
        data: {$seriesJson}
      }],
      chart: {
        type: "bar",
        height: 270,
        toolbar: { show: false },
        animations: { enabled: true, easing: "easeinout", speed: 600 }
      },
      plotOptions: {
        bar: {
          borderRadius: 8,
          columnWidth: "48%",
          distributed: true,
          dataLabels: { position: "top" }
        }
      },
      colors: ["#42A5F5", "#6c66bb", "#00897B", "#FFA000", "#a21ee5"],
      dataLabels: {
        enabled: true,
        formatter: function(val) { return val + " T"; },
        offsetY: -20,
        style: { fontSize: "11px", fontWeight: "700", colors: ["#94a3b8"] }
      },
      xaxis: {
        categories: {$categoriesJson},
        labels: { style: { colors: "#94a3b8", fontSize: "11px", fontWeight: "600" } },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: { colors: "#94a3b8", fontSize: "11px" },
          formatter: function(val) { return val + " T"; }
        }
      },
      grid: {
        borderColor: "rgba(255, 255, 255, 0.08)",
        strokeDashArray: 4
      },
      legend: { show: false },
      tooltip: {
        theme: "{$theme}",
        y: { formatter: function(val) { return val + " Tonnes"; } }
      }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Charges / Purchases Analysis.
     */
    public function generateChargesAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $chargesCount = DB::table('charges')->whereNull('deleted_at')->count();
        $totalWeight = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_weight');
        $totalPrice = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_price');
        $avgPriceKg = $totalWeight > 0 ? ($totalPrice / $totalWeight) : 0;

        // Top 5 Suppliers by total weight
        $topSuppliers = DB::table('charges')
            ->join('suppliers', 'charges.supplier_id', '=', 'suppliers.id')
            ->whereNull('charges.deleted_at')
            ->select('suppliers.id', 'suppliers.name', DB::raw('SUM(charges.total_weight) as weight'), DB::raw('SUM(charges.total_price) as price'), DB::raw('COUNT(charges.id) as count'))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('weight')
            ->limit(5)
            ->get();

        // Recent 5 charges
        $recentCharges = DB::table('charges')
            ->leftJoin('suppliers', 'charges.supplier_id', '=', 'suppliers.id')
            ->leftJoin('ships', 'charges.ship_id', '=', 'ships.id')
            ->whereNull('charges.deleted_at')
            ->select('charges.id', 'charges.date', 'charges.total_weight', 'charges.total_price', 'charges.reception_tracking_number', 'suppliers.name as supplier_name', 'ships.name as ship_name')
            ->orderByDesc('charges.date')
            ->orderByDesc('charges.id')
            ->limit(5)
            ->get();

        $supplierNames = [];
        $supplierWeights = [];
        foreach ($topSuppliers as $s) {
            $supplierNames[] = $s->name ?: 'Fournisseur #' . $s->id;
            $supplierWeights[] = round(((float)$s->weight) / 1000, 2); // in tons
        }

        $sNamesJson = json_encode($supplierNames);
        $sWeightsJson = json_encode($supplierWeights);

        $labels = [
            'fr' => [
                'title' => 'Analyse Approfondie des Achats & Réceptions (Charges)',
                'badge' => 'Module Achats',
                'stat1' => 'Poids Total Acheté',
                'stat2' => 'Montant Global Décaissé',
                'stat3' => 'Prix Moyen Pondéré',
                'stat4' => 'Total Réceptions',
                'chart_title' => 'Top 5 Fournisseurs par Volume (Tonnes)',
                'recent_title' => 'Dernières Réceptions de Poissons',
                'col_id' => 'Lot / Réc.',
                'col_date' => 'Date',
                'col_supp' => 'Fournisseur',
                'col_ship' => 'Navire',
                'col_weight' => 'Poids (KG)',
                'col_price' => 'Montant (MAD)',
                'col_action' => 'Action'
            ],
            'en' => [
                'title' => 'In-depth Purchases & Receptions (Charges) Analysis',
                'badge' => 'Charges Module',
                'stat1' => 'Total Purchased Weight',
                'stat2' => 'Total Purchases Value',
                'stat3' => 'Weighted Average Cost',
                'stat4' => 'Total Receptions',
                'chart_title' => 'Top 5 Suppliers by Volume (Tons)',
                'recent_title' => 'Recent Fish Receptions',
                'col_id' => 'Lot / Rec.',
                'col_date' => 'Date',
                'col_supp' => 'Supplier',
                'col_ship' => 'Vessel',
                'col_weight' => 'Weight (KG)',
                'col_price' => 'Amount (MAD)',
                'col_action' => 'Action'
            ]
        ];

        $t = $labels[$lang] ?? $labels['en'];
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        // Build recent rows HTML
        $recentRows = '';
        foreach ($recentCharges as $c) {
            $tracking = is_array($c->reception_tracking_number) ? implode(', ', $c->reception_tracking_number) : ($c->reception_tracking_number ?: '#' . $c->id);
            $recentRows .= <<<ROW
<tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
  <td style="padding: 10px; font-weight: 700; color: #42A5F5;">{$tracking}</td>
  <td style="padding: 10px; color: {$textColor};">{$c->date}</td>
  <td style="padding: 10px; color: {$textColor};">{$c->supplier_name}</td>
  <td style="padding: 10px; color: {$subColor};">{$c->ship_name}</td>
  <td style="padding: 10px; font-weight: 700; color: {$textColor}; text-align: right;">{$this->formatNumber((float)$c->total_weight, 2)} KG</td>
  <td style="padding: 10px; font-weight: 700; color: #00E676; text-align: right;">{$this->formatNumber((float)$c->total_price, 2)} MAD</td>
  <td style="padding: 10px; text-align: center;">
    <button class="ai-nav-btn v-btn v-btn--variant-tonal v-btn--size-x-small rounded-pill px-3 py-1" style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.3); font-weight: 700; cursor: pointer;" data-route="/charges/view_charge/{$c->id}">
      Voir
    </button>
  </td>
</tr>
ROW;
        }

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(66, 165, 245, 0.25); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(66, 165, 245, 0.15); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #0D47A1, #1565C0, #42A5F5); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.35);">
          <i class="v-icon notranslate mdi mdi-truck-delivery-outline text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #42A5F5;">{$t['title']}</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #42A5F5;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(66, 165, 245, 0.12); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.3); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        {$t['badge']}
      </span>
    </div>

    <!-- 4 KPI Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(66, 165, 245, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">{$t['stat1']}</div>
        <div style="font-size: 18px; font-weight: 800; color: #42A5F5;">{$this->formatNumber($totalWeight, 2)} <span style="font-size: 12px;">KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 230, 118, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">{$t['stat2']}</div>
        <div style="font-size: 18px; font-weight: 800; color: #00E676;">{$this->formatNumber($totalPrice, 2)} <span style="font-size: 12px;">MAD</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(255, 160, 0, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">{$t['stat3']}</div>
        <div style="font-size: 18px; font-weight: 800; color: #FFA000;">{$this->formatNumber($avgPriceKg, 2)} <span style="font-size: 12px;">MAD/KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(171, 71, 188, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">{$t['stat4']}</div>
        <div style="font-size: 18px; font-weight: 800; color: #AB47BC;">{$this->formatNumber($chargesCount, 0)} <span style="font-size: 12px;">Lots</span></div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #42A5F5; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-podium-gold" style="font-size: 16px; margin-right: 6px;"></i>
        {$t['chart_title']}
      </div>
      <div id="{$chartId}" style="min-height: 250px; width: 100%;"></div>
    </div>

    <!-- Recent Charges Table -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 16px; margin-bottom: 18px; overflow-x: auto; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #42A5F5; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-history" style="font-size: 16px; margin-right: 6px;"></i>
        {$t['recent_title']}
      </div>
      <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
        <thead>
          <tr style="border-bottom: 2px solid rgba(66, 165, 245, 0.2); color: {$subColor}; font-size: 11.5px; text-transform: uppercase;">
            <th style="padding: 8px 10px; text-align: left;">{$t['col_id']}</th>
            <th style="padding: 8px 10px; text-align: left;">{$t['col_date']}</th>
            <th style="padding: 8px 10px; text-align: left;">{$t['col_supp']}</th>
            <th style="padding: 8px 10px; text-align: left;">{$t['col_ship']}</th>
            <th style="padding: 8px 10px; text-align: right;">{$t['col_weight']}</th>
            <th style="padding: 8px 10px; text-align: right;">{$t['col_price']}</th>
            <th style="padding: 8px 10px; text-align: center;">{$t['col_action']}</th>
          </tr>
        </thead>
        <tbody>
          {$recentRows}
        </tbody>
      </table>
    </div>

    <!-- Actions -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #42A5F5; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/charges/add_charge">
        <i class="v-icon notranslate mdi mdi-plus-circle" style="font-size: 14px; margin-right: 4px;"></i>
        Ajouter une Charge
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #1565C0; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/charges">
        <i class="v-icon notranslate mdi mdi-format-list-bulleted" style="font-size: 14px; margin-right: 4px;"></i>
        Toutes les Charges
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: [{
        name: "Volume (T)",
        data: {$sWeightsJson}
      }],
      chart: {
        type: "bar",
        height: 250,
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          dataLabels: { position: "top" }
        }
      },
      colors: ["#1565C0", "#1E88E5", "#42A5F5", "#90CAF9", "#00E676"],
      dataLabels: {
        enabled: true,
        formatter: function(val) { return val + " T"; },
        style: { fontSize: "11px", fontWeight: "700", colors: ["#fff"] }
      },
      xaxis: {
        categories: {$sNamesJson},
        labels: { style: { colors: "#94a3b8", fontSize: "11px" } }
      },
      yaxis: {
        labels: { style: { colors: "#94a3b8", fontSize: "11px", fontWeight: "600" } }
      },
      grid: { borderColor: "rgba(255, 255, 255, 0.08)" },
      legend: { show: false },
      tooltip: { theme: "{$theme}" }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Shippings / Sales Analysis.
     */
    public function generateShippingsAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $shippingsCount = DB::table('shippings')->whereNull('deleted_at')->count();
        $totalWeight = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_weight');
        $totalPrice = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_price');
        $avgPriceKg = $totalWeight > 0 ? ($totalPrice / $totalWeight) : 0;

        // Top 5 Clients by total weight
        $topClients = DB::table('shippings')
            ->join('clients', 'shippings.client_id', '=', 'clients.id')
            ->whereNull('shippings.deleted_at')
            ->select('clients.id', 'clients.name', DB::raw('SUM(shippings.total_weight) as weight'), DB::raw('SUM(shippings.total_price) as price'), DB::raw('COUNT(shippings.id) as count'))
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('weight')
            ->limit(5)
            ->get();

        $clientNames = [];
        $clientWeights = [];
        foreach ($topClients as $c) {
            $clientNames[] = $c->name ?: 'Client #' . $c->id;
            $clientWeights[] = round(((float)$c->weight) / 1000, 2);
        }

        $cNamesJson = json_encode($clientNames);
        $cWeightsJson = json_encode($clientWeights);

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(108, 102, 187, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(108, 102, 187, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #372e7d, #4643a0, #6c66bb); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(70, 67, 160, 0.35);">
          <i class="v-icon notranslate mdi mdi-ship-wheel text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #6c66bb;">Analyse des Ventes & Expéditions (Shippings)</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #6c66bb;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(108, 102, 187, 0.15); color: #6c66bb; border: 1px solid rgba(108, 102, 187, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        Module Expéditions
      </span>
    </div>

    <!-- 4 Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(108, 102, 187, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Poids Total Expédié</div>
        <div style="font-size: 18px; font-weight: 800; color: #6c66bb;">{$this->formatNumber($totalWeight, 2)} <span style="font-size: 12px;">KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 230, 118, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Chiffre d'Affaires Global</div>
        <div style="font-size: 18px; font-weight: 800; color: #00E676;">{$this->formatNumber($totalPrice, 2)} <span style="font-size: 12px;">MAD</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(255, 160, 0, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Prix Moyen de Vente</div>
        <div style="font-size: 18px; font-weight: 800; color: #FFA000;">{$this->formatNumber($avgPriceKg, 2)} <span style="font-size: 12px;">MAD/KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(66, 165, 245, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Total Expéditions</div>
        <div style="font-size: 18px; font-weight: 800; color: #42A5F5;">{$this->formatNumber($shippingsCount, 0)} <span style="font-size: 12px;">Lots</span></div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #6c66bb; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-podium" style="font-size: 16px; margin-right: 6px;"></i>
        Top 5 Clients par Volume d'Expédition (Tonnes)
      </div>
      <div id="{$chartId}" style="min-height: 250px; width: 100%;"></div>
    </div>

    <!-- Actions -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #4643a0; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/shippings/add_shipping">
        <i class="v-icon notranslate mdi mdi-plus-circle" style="font-size: 14px; margin-right: 4px;"></i>
        Nouvelle Expédition
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #372e7d; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/shippings">
        <i class="v-icon notranslate mdi mdi-format-list-bulleted" style="font-size: 14px; margin-right: 4px;"></i>
        Toutes les Expéditions
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: [{
        name: "Volume (T)",
        data: {$cWeightsJson}
      }],
      chart: {
        type: "bar",
        height: 250,
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          dataLabels: { position: "top" }
        }
      },
      colors: ["#372e7d", "#4643a0", "#6c66bb", "#8f89e0", "#00E676"],
      dataLabels: {
        enabled: true,
        formatter: function(val) { return val + " T"; },
        style: { fontSize: "11px", fontWeight: "700", colors: ["#fff"] }
      },
      xaxis: {
        categories: {$cNamesJson},
        labels: { style: { colors: "#94a3b8", fontSize: "11px" } }
      },
      yaxis: {
        labels: { style: { colors: "#94a3b8", fontSize: "11px", fontWeight: "600" } }
      },
      grid: { borderColor: "rgba(255, 255, 255, 0.08)" },
      legend: { show: false },
      tooltip: { theme: "{$theme}" }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Production Yield & Processing Analysis.
     */
    public function generateProductionsAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $prodCount = DB::table('productions')->whereNull('deleted_at')->count();
        $freshWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('fresh_reception_weight');
        $frozenWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('frozen_production_weight');
        $guanoWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('guano_production_weight');
        $totalOutput = $frozenWeight + $guanoWeight;
        $yieldRatio = $freshWeight > 0 ? round(($frozenWeight / $freshWeight) * 100, 1) : 0;

        $series = [
            round($frozenWeight / 1000, 2),
            round($guanoWeight / 1000, 2),
        ];
        $labelsArr = ['Congélation Finie (T)', 'Farine & Guano (T)'];
        $seriesJson = json_encode($series);
        $labelsJson = json_encode($labelsArr);

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(0, 137, 123, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(0, 137, 123, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #00695C, #00897B, #26A69A); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 137, 123, 0.35);">
          <i class="v-icon notranslate mdi mdi-factory text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #00897B;">Rendement & Transformation Usine (Production)</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #00897B;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(0, 137, 123, 0.15); color: #00897B; border: 1px solid rgba(0, 137, 123, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        Module Usine
      </span>
    </div>

    <!-- 4 KPI Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 137, 123, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Réception Frais</div>
        <div style="font-size: 18px; font-weight: 800; color: #00897B;">{$this->formatNumber($freshWeight, 2)} <span style="font-size: 12px;">KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(38, 166, 154, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Sortie Congélation</div>
        <div style="font-size: 18px; font-weight: 800; color: #26A69A;">{$this->formatNumber($frozenWeight, 2)} <span style="font-size: 12px;">KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(255, 160, 0, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Guano & Farine</div>
        <div style="font-size: 18px; font-weight: 800; color: #FFA000;">{$this->formatNumber($guanoWeight, 2)} <span style="font-size: 12px;">KG</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 230, 118, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Taux de Rendement</div>
        <div style="font-size: 18px; font-weight: 800; color: #00E676;">{$yieldRatio}%</div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #00897B; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-chart-donut" style="font-size: 16px; margin-right: 6px;"></i>
        Répartition de la Production Finie (Tonnes)
      </div>
      <div id="{$chartId}" style="min-height: 260px; width: 100%;"></div>
    </div>

    <!-- Navigation -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #00897B; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/production/kanban">
        <i class="v-icon notranslate mdi mdi-view-column" style="font-size: 14px; margin-right: 4px;"></i>
        Kanban Production
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #00695C; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/production">
        <i class="v-icon notranslate mdi mdi-format-list-bulleted" style="font-size: 14px; margin-right: 4px;"></i>
        Toutes les Productions
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: {$seriesJson},
      chart: {
        type: "donut",
        height: 260
      },
      labels: {$labelsJson},
      colors: ["#00897B", "#FFA000"],
      legend: {
        position: "bottom",
        labels: { colors: "#94a3b8" }
      },
      tooltip: { theme: "{$theme}" }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Warehouse & Cold Storage Stock Analysis.
     */
    public function generateWarehousesAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $warehousesCount = DB::table('warehouses')->whereNull('deleted_at')->count();
        $totalStockKg = (float) DB::table('warehouses')->whereNull('deleted_at')->sum('total_weight');

        // Top 5 Warehouses by weight
        $topWarehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderByDesc('total_weight')
            ->limit(5)
            ->get();

        $wNames = [];
        $wWeights = [];
        foreach ($topWarehouses as $w) {
            $wNames[] = $w->name;
            $wWeights[] = round(((float)$w->total_weight) / 1000, 2);
        }

        $wNamesJson = json_encode($wNames);
        $wWeightsJson = json_encode($wWeights);

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(162, 30, 229, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(162, 30, 229, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #c015b4, #a21ee5, #9742f5); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(162, 30, 229, 0.35);">
          <i class="v-icon notranslate mdi mdi-warehouse text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #a21ee5;">Stocks & Chambres Froides (Warehouses)</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #a21ee5;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(162, 30, 229, 0.15); color: #a21ee5; border: 1px solid rgba(162, 30, 229, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        Module Magasins
      </span>
    </div>

    <!-- 2 Main Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(162, 30, 229, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Stock Global Actuel</div>
        <div style="font-size: 20px; font-weight: 800; color: #a21ee5;">{$this->formatNumber($totalStockKg, 2)} <span style="font-size: 13px;">KG ({$this->formatNumber($totalStockKg/1000, 2)} T)</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(66, 165, 245, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Nombre d'Entrepôts / Chambres</div>
        <div style="font-size: 20px; font-weight: 800; color: #42A5F5;">{$warehousesCount} <span style="font-size: 13px;">Chambres Actives</span></div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #a21ee5; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-podium" style="font-size: 16px; margin-right: 6px;"></i>
        Top 5 Entrepôts par Charge de Stock (Tonnes)
      </div>
      <div id="{$chartId}" style="min-height: 250px; width: 100%;"></div>
    </div>

    <!-- Actions -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #a21ee5; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/warehouses">
        <i class="v-icon notranslate mdi mdi-warehouse" style="font-size: 14px; margin-right: 4px;"></i>
        Gestion des Magasins
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #c015b4; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/warehouses/balance_tracking">
        <i class="v-icon notranslate mdi mdi-scale-balance" style="font-size: 14px; margin-right: 4px;"></i>
        Balance Stock
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: [{
        name: "Stock (T)",
        data: {$wWeightsJson}
      }],
      chart: {
        type: "bar",
        height: 250,
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          dataLabels: { position: "top" }
        }
      },
      colors: ["#a21ee5", "#c015b4", "#9742f5", "#ba68c8", "#ce93d8"],
      dataLabels: {
        enabled: true,
        formatter: function(val) { return val + " T"; },
        style: { fontSize: "11px", fontWeight: "700", colors: ["#fff"] }
      },
      xaxis: {
        categories: {$wNamesJson},
        labels: { style: { colors: "#94a3b8", fontSize: "11px" } }
      },
      yaxis: {
        labels: { style: { colors: "#94a3b8", fontSize: "11px", fontWeight: "600" } }
      },
      grid: { borderColor: "rgba(255, 255, 255, 0.08)" },
      legend: { show: false },
      tooltip: { theme: "{$theme}" }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Workforce & Fortnight Activity Analysis.
     */
    public function generateWorkforceAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $workforceCount = DB::table('workforces')->whereNull('deleted_at')->count();
        $activitiesCount = DB::table('fortnight_activities')->whereNull('deleted_at')->count();
        $totalPayroll = (float) DB::table('fortnight_activities')->whereNull('deleted_at')->sum('price');
        $avgDailyRate = $activitiesCount > 0 ? round($totalPayroll / $activitiesCount, 2) : 0;

        // Top 5 Sections by activity count
        $topSections = DB::table('fortnight_activities')
            ->join('sections', 'fortnight_activities.section_id', '=', 'sections.id')
            ->whereNull('fortnight_activities.deleted_at')
            ->select('sections.id', 'sections.name', DB::raw('COUNT(fortnight_activities.id) as act_count'), DB::raw('SUM(fortnight_activities.price) as total_price'))
            ->groupBy('sections.id', 'sections.name')
            ->orderByDesc('act_count')
            ->limit(5)
            ->get();

        $secNames = [];
        $secCounts = [];
        foreach ($topSections as $sec) {
            $secNames[] = $sec->name ?: 'Section #' . $sec->id;
            $secCounts[] = (int)$sec->act_count;
        }

        $sNamesJson = json_encode($secNames);
        $sCountsJson = json_encode($secCounts);

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(66, 165, 245, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(66, 165, 245, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #1565C0, #1E88E5, #42A5F5); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.35);">
          <i class="v-icon notranslate mdi mdi-account-group text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #42A5F5;">Main d'Œuvre & Activités Quinzaine (Workforce)</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #42A5F5;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        Module Personnel
      </span>
    </div>

    <!-- 4 KPI Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(66, 165, 245, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Effectif Ouvriers</div>
        <div style="font-size: 18px; font-weight: 800; color: #42A5F5;">{$this->formatNumber($workforceCount, 0)} <span style="font-size: 12px;">Employés</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 230, 118, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Masse Salariale Fortnight</div>
        <div style="font-size: 18px; font-weight: 800; color: #00E676;">{$this->formatNumber($totalPayroll, 2)} <span style="font-size: 12px;">MAD</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(255, 160, 0, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Total Postes / Activités</div>
        <div style="font-size: 18px; font-weight: 800; color: #FFA000;">{$this->formatNumber($activitiesCount, 0)} <span style="font-size: 12px;">Jours</span></div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(171, 71, 188, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Tarif Journalier Moyen</div>
        <div style="font-size: 18px; font-weight: 800; color: #AB47BC;">{$this->formatNumber($avgDailyRate, 2)} <span style="font-size: 12px;">MAD/J</span></div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #42A5F5; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-view-week" style="font-size: 16px; margin-right: 6px;"></i>
        Répartition des Activités par Section Industrielle
      </div>
      <div id="{$chartId}" style="min-height: 250px; width: 100%;"></div>
    </div>

    <!-- Actions -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #42A5F5; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/fortnight-management">
        <i class="v-icon notranslate mdi mdi-calendar-clock" style="font-size: 14px; margin-right: 4px;"></i>
        Fortnight Management
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #1565C0; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/workforces">
        <i class="v-icon notranslate mdi mdi-account-multiple" style="font-size: 14px; margin-right: 4px;"></i>
        Liste des Ouvriers
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: [{
        name: "Activités",
        data: {$sCountsJson}
      }],
      chart: {
        type: "bar",
        height: 250,
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: false,
          columnWidth: "45%",
          distributed: true,
          dataLabels: { position: "top" }
        }
      },
      colors: ["#1565C0", "#1E88E5", "#42A5F5", "#00E676", "#FFA000"],
      dataLabels: {
        enabled: true,
        formatter: function(val) { return val; },
        offsetY: -20,
        style: { fontSize: "11px", fontWeight: "700", colors: ["#94a3b8"] }
      },
      xaxis: {
        categories: {$sNamesJson},
        labels: { style: { colors: "#94a3b8", fontSize: "11px" } }
      },
      yaxis: {
        labels: { style: { colors: "#94a3b8", fontSize: "11px" } }
      },
      grid: { borderColor: "rgba(255, 255, 255, 0.08)" },
      legend: { show: false },
      tooltip: { theme: "{$theme}" }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates Finances, Invoices & Settlements Analysis.
     */
    public function generateFinancesAnalysis(string $uniqueId, string $chartId, string $question, string $lang, string $theme): string
    {
        $suppInvCount = DB::table('supplier_invoices')->whereNull('deleted_at')->count();
        $suppInvPrice = (float) DB::table('supplier_invoices')->whereNull('deleted_at')->sum('total_price');

        $clientInvCount = DB::table('client_invoices')->whereNull('deleted_at')->count();
        $clientInvPrice = (float) DB::table('client_invoices')->whereNull('deleted_at')->sum('total_price');

        $series = [
            round($suppInvPrice, 2),
            round($clientInvPrice, 2)
        ];
        $labelsArr = ['Factures Fournisseurs (MAD)', 'Factures Clients (MAD)'];
        $seriesJson = json_encode($series);
        $labelsJson = json_encode($labelsArr);

        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(230, 81, 0, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(230, 81, 0, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #E65100, #F57C00, #FFB74D); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(230, 81, 0, 0.35);">
          <i class="v-icon notranslate mdi mdi-file-document-multiple-outline text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #E65100;">Finances & Facturation (Invoices)</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #E65100;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(230, 81, 0, 0.15); color: #E65100; border: 1px solid rgba(230, 81, 0, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        Module Facturation
      </span>
    </div>

    <!-- 2 Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(230, 81, 0, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Facturation Fournisseurs</div>
        <div style="font-size: 20px; font-weight: 800; color: #E65100;">{$this->formatNumber($suppInvPrice, 2)} <span style="font-size: 13px;">MAD</span></div>
        <div style="font-size: 11.5px; color: {$subColor}; margin-top: 4px;">{$suppInvCount} Factures Fournisseurs</div>
      </div>
      <div style="background: {$innerBg}; border-radius: 14px; padding: 14px; border: 1px solid rgba(0, 131, 143, 0.2);">
        <div style="font-size: 11.5px; font-weight: 700; color: {$subColor}; margin-bottom: 4px;">Facturation Clients</div>
        <div style="font-size: 20px; font-weight: 800; color: #00838F;">{$this->formatNumber($clientInvPrice, 2)} <span style="font-size: 13px;">MAD</span></div>
        <div style="font-size: 11.5px; color: {$subColor}; margin-top: 4px;">{$clientInvCount} Factures Clients</div>
      </div>
    </div>

    <!-- Chart -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 18px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 13px; font-weight: 800; color: #E65100; text-transform: uppercase; margin-bottom: 12px;">
        <i class="v-icon notranslate mdi mdi-scale-balance" style="font-size: 16px; margin-right: 6px;"></i>
        Équilibre Financier (Fournisseurs vs Clients)
      </div>
      <div id="{$chartId}" style="min-height: 250px; width: 100%;"></div>
    </div>

    <!-- Actions -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #E65100; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/supplier-invoices">
        <i class="v-icon notranslate mdi mdi-file-document" style="font-size: 14px; margin-right: 4px;"></i>
        Factures Fournisseurs
      </button>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: #00838F; color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/client-invoices">
        <i class="v-icon notranslate mdi mdi-file-document-outline" style="font-size: 14px; margin-right: 4px;"></i>
        Factures Clients
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  function initChart() {
    if (!window.ApexCharts) {
      setTimeout(initChart, 100);
      return;
    }
    var el = document.querySelector("#{$chartId}");
    if (!el) return;
    el.innerHTML = "";
    var options = {
      series: {$seriesJson},
      chart: {
        type: "donut",
        height: 250
      },
      labels: {$labelsJson},
      colors: ["#E65100", "#00838F"],
      legend: { position: "bottom", labels: { colors: "#94a3b8" } },
      tooltip: {
        theme: "{$theme}",
        y: { formatter: function(val) { return val.toLocaleString() + " MAD"; } }
      }
    };
    var chart = new ApexCharts(el, options);
    chart.render();
  }
  setTimeout(initChart, 60);
})();
</script>
HTML;
    }

    /**
     * Generates analysis for uploaded Excel files.
     */
    public function generateExcelAnalysisResponse(string $uniqueId, string $chartId, string $question, array $excelFiles, string $lang, string $theme): string
    {
        $fileCount = count($excelFiles);
        $totalRows = 0;
        $headers = [];
        $firstFileName = '';

        foreach ($excelFiles as $f) {
            $totalRows += count($f['rows'] ?? []);
            if (empty($headers)) {
                $headers = $f['headers'] ?? [];
                $firstFileName = $f['name'] ?? 'Data';
            }
        }

        $previewRows = array_slice($excelFiles[0]['rows'] ?? [], 0, 5);
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        $thHtml = '';
        foreach (array_slice($headers, 0, 5) as $h) {
            $thHtml .= "<th style=\"padding: 8px 10px; text-align: left;\">{$h}</th>";
        }

        $trHtml = '';
        foreach ($previewRows as $r) {
            $trHtml .= "<tr style=\"border-bottom: 1px solid rgba(255,255,255,0.06);\">";
            foreach (array_slice($headers, 0, 5) as $h) {
                $val = is_scalar($r[$h] ?? null) ? $r[$h] : '';
                $trHtml .= "<td style=\"padding: 8px 10px; color: {$textColor};\">{$val}</td>";
            }
            $trHtml .= "</tr>";
        }

        return <<<HTML
<div id="{$uniqueId}">
  <div style="background: {$cardBg}; border-radius: 20px; padding: 22px; border: 1px solid rgba(76, 175, 80, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(76, 175, 80, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #1B5E20, #2E7D32, #4CAF50); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(46, 125, 50, 0.35);">
          <i class="v-icon notranslate mdi mdi-file-excel-outline text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #4CAF50;">Analyse Fichier Excel Importé: {$firstFileName}</div>
          <div style="font-size: 11.5px; color: {$subColor}; font-weight: 600;">Supervised by <strong style="color: #4CAF50;">ELBACHIR NOUBAA</strong></div>
        </div>
      </div>
      <span style="background: rgba(76, 175, 80, 0.15); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        {$totalRows} Lignes Traitées
      </span>
    </div>

    <!-- Table Preview -->
    <div style="background: {$innerBg}; border-radius: 16px; padding: 16px; margin-bottom: 16px; overflow-x: auto; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 12.5px; font-weight: 800; color: #4CAF50; text-transform: uppercase; margin-bottom: 10px;">
        <i class="v-icon notranslate mdi mdi-table" style="font-size: 16px; margin-right: 6px;"></i>
        Aperçu des Données du Fichier
      </div>
      <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
        <thead>
          <tr style="border-bottom: 2px solid rgba(76, 175, 80, 0.2); color: {$subColor}; font-size: 11.5px; text-transform: uppercase;">
            {$thHtml}
          </tr>
        </thead>
        <tbody>
          {$trHtml}
        </tbody>
      </table>
    </div>

    <div style="font-size: 13.5px; color: {$textColor}; line-height: 1.7; background: rgba(76, 175, 80, 0.08); padding: 14px; border-radius: 12px; border-left: 4px solid #4CAF50;">
      Les données Excel ont été chargées et indexées avec succès. Vous pouvez poser des questions spécifiques sur ces colonnes (ex: totaux, moyennes, classements, filtres) ou comparer avec la base de données.
    </div>
  </div>
</div>
HTML;
    }

    /**
     * Format number with spaces as thousands separator and decimals.
     */
    private function formatNumber(float|int $num, int $decimals = 2): string
    {
        return number_format($num, $decimals, '.', ' ');
    }
}

