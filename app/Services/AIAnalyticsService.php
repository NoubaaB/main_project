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
     * Main entry point to process a question and generate a rich, accurate response
     * tailored precisely to what the user asked.
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

        // 3. Parse question parameters (domain, intent, timeFrame, filter, limit)
        $p = $this->parseQuestion($question, $lang);

        // 4. Route to specialized domain handler
        switch ($p['domain']) {
            case 'fortnight_monthly':
                return $this->handleFortnightMonthlyQuery($uniqueId, $chartId, $p, $theme);
            case 'charges_categories':
                return $this->handleChargesCategoriesQuery($uniqueId, $chartId, $p, $theme);
            case 'shippings_categories':
                return $this->handleShippingsCategoriesQuery($uniqueId, $chartId, $p, $theme);
            case 'productions_categories':
                return $this->handleProductionsCategoriesQuery($uniqueId, $chartId, $p, $theme);
            case 'charges_monthly':
                return $this->handleChargesMonthlyQuery($uniqueId, $chartId, $p, $theme);
            case 'shippings_monthly':
                return $this->handleShippingsMonthlyQuery($uniqueId, $chartId, $p, $theme);
            case 'productions_monthly':
                return $this->handleProductionsMonthlyQuery($uniqueId, $chartId, $p, $theme);
            case 'workforce_sections':
                return $this->handleWorkforceSectionsQuery($uniqueId, $chartId, $p, $theme);
            case 'workforce_corporals':
                return $this->handleWorkforceCorporalsQuery($uniqueId, $chartId, $p, $theme);
            case 'charges':
                return $this->handleChargesQuery($uniqueId, $chartId, $p, $theme);
            case 'shippings':
                return $this->handleShippingsQuery($uniqueId, $chartId, $p, $theme);
            case 'productions':
                return $this->handleProductionsQuery($uniqueId, $chartId, $p, $theme);
            case 'warehouses':
                return $this->handleWarehousesQuery($uniqueId, $chartId, $p, $theme);
            case 'invoices':
                return $this->handleInvoicesQuery($uniqueId, $chartId, $p, $theme);
            case 'workforce':
                return $this->handleWorkforceQuery($uniqueId, $chartId, $p, $theme);
            case 'trucks':
                return $this->handleTrucksQuery($uniqueId, $chartId, $p, $theme);
            case 'ships':
                return $this->handleShipsQuery($uniqueId, $chartId, $p, $theme);
            case 'fish_types':
                return $this->handleFishTypesQuery($uniqueId, $chartId, $p, $theme);
            case 'suppliers':
                return $this->handleSuppliersQuery($uniqueId, $chartId, $p, $theme);
            case 'clients':
                return $this->handleClientsQuery($uniqueId, $chartId, $p, $theme);
            case 'comparison':
                return $this->handleComparisonQuery($uniqueId, $chartId, $p, $theme);
            case 'search':
                return $this->handleGenericSearchQuery($uniqueId, $chartId, $p, $theme);
            case 'overview':
            default:
                return $this->handleOverviewQuery($uniqueId, $chartId, $p, $theme);
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
        if (preg_match('/\b(qui|est|sont|les|des|du|dans|pour|avec|donne|moi|affiche|combien|achats?|charges?|ventes?|expéditions?|productions?|stocks?|magasins?|entrepôts?|main|oeuvre|ouvriers?|factures?|règlements?|créances?|dettes?|résumés?|global|aujourd|hier|mois|année|rendement|poissons?|especes?|espèces?|fournisseurs?|clients?|prix|totals?|poids|derniers?|dernières?|camions?|navires?|bateaux?|liste|tableau|meilleurs?|cherche|montre|évolution|quinzaines?|caporaux|sections?)\b/ui', $lower)) {
            return 'fr';
        }

        // Spanish signals
        if (preg_match('/\b(quien|es|son|los|las|para|con|dame|muestra|cuanto|compras|ventas|produccion|almacen|pescado|proveedor|cliente|factura|total|peso|precio|este|mes|ano|hoy|ayer|camiones|barcos|ultimos|ultimas|lista|tabla)\b/ui', $lower)) {
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
     * Parse the user question into actionable parameters.
     */
    private function parseQuestion(string $question, string $lang): array
    {
        $lower = mb_strtolower($question, 'UTF-8');

        // 1. Year extraction (handles "inyear 2026", "inyear2026", "year 2026", "en 2026", "2026", etc.)
        $year = null;
        if (preg_match('/(?:inyear|year|en l\'année|année|año)?\s*\b(20[2-3][0-9])\b/ui', $lower, $ym)) {
            $year = (int)$ym[1];
        } elseif (preg_match('/20[2-3][0-9]/', $lower, $ym)) {
            $year = (int)$ym[0];
        }

        // 2. Limit extraction (default: 10 for lists, 5 for rankings)
        $limit = 10;
        if (preg_match('/\b(top\s*)?(\d+)\b/u', $lower, $m)) {
            $val = (int)$m[2];
            if ($val > 0 && $val <= 100) {
                $limit = $val;
            }
        }

        // 3. Intent extraction (handles typos like "totoal", "totale", etc.)
        $intent = 'summary';
        if (preg_match('/\b(liste|donne|donne-moi|affiche|tableau|montre|montrez|voir|derniers?|dernières?|récents?|table|list|show)\b/ui', $lower)) {
            $intent = 'list';
        } elseif (preg_match('/\b(combien|nombre|count|cuantos?)\b/ui', $lower)) {
            $intent = 'count';
        } elseif (preg_match('/\b(total|totoal|totale?|totaux|somme|chiffre d\'affaires|valeur|montant|sum)\b/ui', $lower)) {
            $intent = 'total';
        } elseif (preg_match('/\b(moyen|moyenne|average|wap)\b/ui', $lower)) {
            $intent = 'average';
        } elseif (preg_match('/\b(meilleur|meilleurs|top|plus grand|plus vendu|ranking)\b/ui', $lower)) {
            $intent = 'top';
        } elseif (preg_match('/\b(impayé|impayés|créance|créances|dette|dettes|non réglé|unpaid)\b/ui', $lower)) {
            $intent = 'unpaid';
        } elseif (preg_match('/\b(évolution|tendance|comparer|comparaison|graphique|chart|proper chart|trend)\b/ui', $lower)) {
            $intent = 'trend';
        }

        // 4. TimeFrame extraction
        $timeFrame = 'all';
        if ($year !== null) {
            $timeFrame = 'year_' . $year;
        } elseif (preg_match('/\b(aujourd\'?hui|today|hoy)\b/ui', $lower)) {
            $timeFrame = 'today';
        } elseif (preg_match('/\b(hier|yesterday|ayer)\b/ui', $lower)) {
            $timeFrame = 'yesterday';
        } elseif (preg_match('/\b(cette semaine|this week|esta semana)\b/ui', $lower)) {
            $timeFrame = 'this_week';
        } elseif (preg_match('/\b(ce mois|ce mois-ci|this month|este mes)\b/ui', $lower)) {
            $timeFrame = 'this_month';
        } elseif (preg_match('/\b(mois dernier|le mois passé|last month|mes pasado)\b/ui', $lower)) {
            $timeFrame = 'last_month';
        }

        // Monthly / period check (handles "in each month", "each month", "all month", "par mois", etc.)
        $isMonthly = (bool) preg_match('/(month|months|mois|mes|meses|monthly|mensuel|mensuelle|mensuels|each month|by month|par mois|por mes|all month|chaque mois|tous les mois)/ui', $lower);

        // Fortnight signals
        $isFortnight = (bool) preg_match('/(fortnight|fortnights|quinzaine|quinzaines|quincena|quincenas|\bq1\b|\bq2\b)/ui', $lower);

        // Section & Corporal signals
        $isSection = (bool) preg_match('/(section|sections)/ui', $lower);
        $isCorporal = (bool) preg_match('/(caporal|caporaux|corporal|corporals)/ui', $lower);

        // Charges / Purchases signals
        $isCharges = (bool) preg_match('/(charge|charges|achat|achats|réception|réceptions|purchases?)/ui', $lower);

        // Shippings / Sales signals
        $isShippings = (bool) preg_match('/(shipping|shippings|vente|ventes|expédition|expéditions|sales?)/ui', $lower);

        // Productions signals
        $isProductions = (bool) preg_match('/(production|productions|congélation|frais|guano|farine|huile|rendement|yield|equilibrium)/ui', $lower);

        // Category breakdown signals
        $isCategory = (bool) preg_match('/(catégorie|catégories|categorie|categories|category)/ui', $lower);

        // 5. Domain extraction
        $domain = 'overview';
        if ($isFortnight && $isMonthly) {
            $domain = 'fortnight_monthly';
        } elseif ($isCharges && $isCategory) {
            $domain = 'charges_categories';
        } elseif ($isShippings && $isCategory) {
            $domain = 'shippings_categories';
        } elseif ($isProductions && $isCategory) {
            $domain = 'productions_categories';
        } elseif ($isCharges && $isMonthly) {
            $domain = 'charges_monthly';
        } elseif ($isShippings && $isMonthly) {
            $domain = 'shippings_monthly';
        } elseif ($isProductions && $isMonthly) {
            $domain = 'productions_monthly';
        } elseif ($isFortnight && $isSection) {
            $domain = 'workforce_sections';
        } elseif ($isFortnight && $isCorporal) {
            $domain = 'workforce_corporals';
        } elseif (preg_match('/\b(comparer?|comparaison|versus|vs|bilan)\b/ui', $lower) && preg_match('/\b(achat|charge|vente|shipping)\b/ui', $lower)) {
            $domain = 'comparison';
        } elseif (preg_match('/\b(camion|camions|matricule|véhicule|véhicules|truck|trucks)\b/ui', $lower)) {
            $domain = 'trucks';
        } elseif (preg_match('/\b(navire|navires|bateau|bateaux|barque|ship|ships|vessel|vessels)\b/ui', $lower)) {
            $domain = 'ships';
        } elseif (preg_match('/\b(facture|factures|invoices?|règlement|règlements|paiement|paiements|payments?|créance|créances|dette|dettes|impayé|impayés)\b/ui', $lower)) {
            $domain = 'invoices';
        } elseif (preg_match('/\b(fournisseur|fournisseurs|suppliers?|proveedor)\b/ui', $lower) && preg_match('/\b(liste|qui sont|top|meilleur)\b/ui', $lower)) {
            $domain = 'suppliers';
        } elseif (preg_match('/\b(client|clients|acheteurs?)\b/ui', $lower) && preg_match('/\b(liste|qui sont|top|meilleur)\b/ui', $lower)) {
            $domain = 'clients';
        } elseif ($isCharges) {
            $domain = 'charges';
        } elseif ($isShippings) {
            $domain = 'shippings';
        } elseif ($isProductions) {
            $domain = 'productions';
        } elseif (preg_match('/\b(stock|stocks|entrepôt|entrepôts|magasin|magasins|chambre|chambres|warehouse|warehouses|inventaire|inventaires|balance)\b/ui', $lower)) {
            $domain = 'warehouses';
        } elseif ($isSection) {
            $domain = 'workforce_sections';
        } elseif ($isCorporal) {
            $domain = 'workforce_corporals';
        } elseif (preg_match('/\b(ouvrier|ouvriers|personnel|salarié|salariés|main|oeuvre|workforce|quinzaine|fortnight)\b/ui', $lower)) {
            $domain = 'workforce';
        } elseif (preg_match('/\b(poisson|poissons|espèce|espèces|sardine|maquereau|chinchard|anchois|thon|poulpe|seiche|calamar|merlu|sole|fish)\b/ui', $lower)) {
            $domain = 'fish_types';
        } elseif (preg_match('/\b(cherche|trouve|search|id|#\d+)\b/ui', $lower)) {
            $domain = 'search';
        }

        // 6. Keyword extraction for specific entities (e.g., sardine, driss, etc.)
        $keyword = null;
        if (preg_match('/\b(sardine|maquereau|chinchard|anchois|thon|poulpe|seiche|calamar|merlu|sole|pageot|bogue)\b/ui', $lower, $m)) {
            $keyword = $m[1];
        }

        return [
            'question' => $question,
            'lang' => $lang,
            'domain' => $domain,
            'intent' => $intent,
            'limit' => $limit,
            'year' => $year,
            'timeFrame' => $timeFrame,
            'isMonthly' => $isMonthly,
            'keyword' => $keyword,
        ];
    }

    // =========================================================================
    //  DOMAIN HANDLERS
    // =========================================================================

    /**
     * Handle Fortnight Monthly Breakdown queries (Q1 and Q2 totals for each month in a year).
     */
    private function handleFortnightMonthlyQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: (int) (DB::table('fortnight_activities')->whereNull('deleted_at')->max(DB::raw('YEAR(date)')) ?: 2026);

        $rows = DB::table('fortnight_activities')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->selectRaw("
                MONTH(date) as month_num,
                SUM(CASE WHEN fortnight_period = 'Q1' THEN price ELSE 0 END) as q1_price,
                SUM(CASE WHEN fortnight_period = 'Q1' THEN 1 ELSE 0 END) as q1_days,
                COUNT(DISTINCT CASE WHEN fortnight_period = 'Q1' THEN workforce_id ELSE NULL END) as q1_workers,
                SUM(CASE WHEN fortnight_period = 'Q2' THEN price ELSE 0 END) as q2_price,
                SUM(CASE WHEN fortnight_period = 'Q2' THEN 1 ELSE 0 END) as q2_days,
                COUNT(DISTINCT CASE WHEN fortnight_period = 'Q2' THEN workforce_id ELSE NULL END) as q2_workers,
                SUM(price) as month_total_price,
                COUNT(*) as month_total_days,
                COUNT(DISTINCT workforce_id) as month_workers
            ")
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get();

        $monthNames = match ($lang) {
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'es' => [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'],
            'ar' => [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'ماي', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت', 9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'],
            default => [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'],
        };

        $chartLabels = [];
        $q1SeriesData = [];
        $q2SeriesData = [];
        $totalQ1Price = 0;
        $totalQ1Days = 0;
        $totalQ2Price = 0;
        $totalQ2Days = 0;
        $grandTotalPrice = 0;
        $grandTotalDays = 0;

        $tableRowsHtml = '';
        foreach ($rows as $r) {
            $mNum = (int)$r->month_num;
            $mName = $monthNames[$mNum] ?? "Month {$mNum}";
            $chartLabels[] = $mName;

            $q1P = (float)$r->q1_price;
            $q1D = (int)$r->q1_days;
            $q2P = (float)$r->q2_price;
            $q2D = (int)$r->q2_days;
            $mP = (float)$r->month_total_price;
            $mD = (int)$r->month_total_days;

            $q1SeriesData[] = round($q1P, 2);
            $q2SeriesData[] = round($q2P, 2);

            $totalQ1Price += $q1P;
            $totalQ1Days += $q1D;
            $totalQ2Price += $q2P;
            $totalQ2Days += $q2D;
            $grandTotalPrice += $mP;
            $grandTotalDays += $mD;

            $q1PriceFmt = $this->formatNumber($q1P, 2);
            $q1DaysFmt = $this->formatNumber($q1D, 0);
            $q2PriceFmt = $this->formatNumber($q2P, 2);
            $q2DaysFmt = $this->formatNumber($q2D, 0);
            $mPriceFmt = $this->formatNumber($mP, 2);
            $mDaysFmt = $this->formatNumber($mD, 0);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                  <i class="v-icon notranslate mdi mdi-calendar-month text-blue" style="font-size: 15px;"></i>
                  {$mName}
                </span>
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #42A5F5;">{$q1PriceFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$q1DaysFmt} j</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #FFB74D;">{$q2PriceFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$q2DaysFmt} j</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #00E676;">{$mPriceFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$mDaysFmt} j</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
                  Consulter
                </button>
              </td>
            </tr>
TR;
        }

        // Add Grand Total Summary Row
        $totQ1PriceFmt = $this->formatNumber($totalQ1Price, 2);
        $totQ1DaysFmt = $this->formatNumber($totalQ1Days, 0);
        $totQ2PriceFmt = $this->formatNumber($totalQ2Price, 2);
        $totQ2DaysFmt = $this->formatNumber($totalQ2Days, 0);
        $grandTotPriceFmt = $this->formatNumber($grandTotalPrice, 2);
        $grandTotDaysFmt = $this->formatNumber($grandTotalDays, 0);

        $totLabel = match ($lang) {
            'en' => "TOTAL YEAR {$year}",
            'es' => "TOTAL AÑO {$year}",
            'ar' => "إجمالي سنة {$year}",
            default => "TOTAL ANNUEL {$year}",
        };

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(66, 165, 245, 0.12); font-weight: 800; border-top: 2px solid #42A5F5; border-bottom: 2px solid #42A5F5;">
          <td style="padding: 12px; font-weight: 900; color: #42A5F5; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
            <i class="v-icon notranslate mdi mdi-sigma text-blue" style="font-size: 18px; margin-right: 4px;"></i>
            {$totLabel}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #42A5F5; font-size: 13px;">{$totQ1PriceFmt} MAD</td>
          <td style="padding: 12px; text-align: right; font-weight: 800; font-size: 12.5px;">{$totQ1DaysFmt} j</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #FFB74D; font-size: 13px;">{$totQ2PriceFmt} MAD</td>
          <td style="padding: 12px; text-align: right; font-weight: 800; font-size: 12.5px;">{$totQ2DaysFmt} j</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #00E676; font-size: 14px;">{$grandTotPriceFmt} MAD</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; font-size: 13px;">{$grandTotDaysFmt} j</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #1565C0; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
              Détails
            </button>
          </td>
        </tr>
TR;

        // Build localized answer text
        $answerText = match ($lang) {
            'en' => "Here is the monthly breakdown of fortnights (Q1 & Q2) for the year {$year}: Total expenditure is {$grandTotPriceFmt} MAD across {$grandTotDaysFmt} working days ({$totQ1PriceFmt} MAD for Quinzaine 1 and {$totQ2PriceFmt} MAD for Quinzaine 2). Below is the comprehensive matrix table and comparative monthly chart.",
            'es' => "Aquí está el desglose mensual de las quincenas (Q1 y Q2) para el año {$year}: Gasto total de {$grandTotPriceFmt} MAD en {$grandTotDaysFmt} jornadas de trabajo ({$totQ1PriceFmt} MAD para la Quincena 1 y {$totQ2PriceFmt} MAD para la Quincena 2). A continuación se muestra la tabla matricial y el gráfico comparativo.",
            'ar' => "إليك التفصيل الشهري لنتائج وسجلات النصف-شهرية (Q1 و Q2) لسنة {$year}: إجمالي الأجور المصروفة بلغ {$grandTotPriceFmt} درهم عبر {$grandTotDaysFmt} يوم عمل (الشطر الأول Q1: {$totQ1PriceFmt} درهم والشطر الثاني Q2: {$totQ2PriceFmt} درهم). ستجد أسفله جدول المقارنة والرسم البياني المفصل.",
            default => "Voici le récapitulatif mensuel détaillé des quinzaines (Q1 & Q2) pour l'année {$year} : Masse salariale globale de {$grandTotPriceFmt} MAD sur {$grandTotDaysFmt} journées de travail ({$totQ1PriceFmt} MAD pour la Quinzaine 1 et {$totQ2PriceFmt} MAD pour la Quinzaine 2). Retrouvez ci-dessous la matrice par mois et le graphique comparatif.",
        };

        $headers = match ($lang) {
            'en' => [
                ['title' => 'Month', 'align' => 'left'],
                ['title' => 'Quinzaine 1 (Q1) - MAD', 'align' => 'right'],
                ['title' => 'Q1 Days', 'align' => 'right'],
                ['title' => 'Quinzaine 2 (Q2) - MAD', 'align' => 'right'],
                ['title' => 'Q2 Days', 'align' => 'right'],
                ['title' => 'Month Total (MAD)', 'align' => 'right'],
                ['title' => 'Month Days', 'align' => 'right'],
                ['title' => 'Action', 'align' => 'center'],
            ],
            'es' => [
                ['title' => 'Mes', 'align' => 'left'],
                ['title' => 'Quincena 1 (Q1) - MAD', 'align' => 'right'],
                ['title' => 'Días Q1', 'align' => 'right'],
                ['title' => 'Quincena 2 (Q2) - MAD', 'align' => 'right'],
                ['title' => 'Días Q2', 'align' => 'right'],
                ['title' => 'Total Mes (MAD)', 'align' => 'right'],
                ['title' => 'Total Días', 'align' => 'right'],
                ['title' => 'Acción', 'align' => 'center'],
            ],
            'ar' => [
                ['title' => 'الشهر', 'align' => 'left'],
                ['title' => 'الشطر الأول (Q1) - درهم', 'align' => 'right'],
                ['title' => 'أيام Q1', 'align' => 'right'],
                ['title' => 'الشطر الثاني (Q2) - درهم', 'align' => 'right'],
                ['title' => 'أيام Q2', 'align' => 'right'],
                ['title' => 'مجموع الشهر (درهم)', 'align' => 'right'],
                ['title' => 'إجمالي الأيام', 'align' => 'right'],
                ['title' => 'إجراء', 'align' => 'center'],
            ],
            default => [
                ['title' => 'Mois', 'align' => 'left'],
                ['title' => 'Quinzaine 1 (Q1) - MAD', 'align' => 'right'],
                ['title' => 'Q1 Journées', 'align' => 'right'],
                ['title' => 'Quinzaine 2 (Q2) - MAD', 'align' => 'right'],
                ['title' => 'Q2 Journées', 'align' => 'right'],
                ['title' => 'Total Mois (MAD)', 'align' => 'right'],
                ['title' => 'Total Journées', 'align' => 'right'],
                ['title' => 'Action', 'align' => 'center'],
            ],
        };

        $chartTitle = match ($lang) {
            'en' => "Monthly Fortnight Comparison (Q1 vs Q2) - Year {$year} (MAD)",
            'es' => "Comparación Mensual de Quincenas (Q1 vs Q2) - Año {$year} (MAD)",
            'ar' => "مقارنة أشطر النصف-شهرية (Q1 مقابل Q2) حسب الشهر - سنة {$year} (درهم)",
            default => "Comparaison Mensuelle des Quinzaines (Q1 vs Q2) - Année {$year} (MAD)",
        };

        $seriesNameQ1 = match ($lang) {
            'en' => "Quinzaine 1 (Q1)",
            'es' => "Quincena 1 (Q1)",
            'ar' => "الشطر 1 (Q1)",
            default => "Quinzaine 1 (Q1)",
        };

        $seriesNameQ2 = match ($lang) {
            'en' => "Quinzaine 2 (Q2)",
            'es' => "Quincena 2 (Q2)",
            'ar' => "الشطر 2 (Q2)",
            default => "Quinzaine 2 (Q2)",
        };

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => ($lang === 'en' ? "Fortnight & Workforce Module - Year {$year}" : "Module Quinzaine & Main d'œuvre - Année {$year}"),
            'moduleColor' => '#1565C0',
            'moduleIcon' => 'mdi-calendar-month',
            'badgeText' => ($lang === 'en' ? "Monthly Fortnight Breakdown {$year}" : "Matrice Mensuelle Quinzaines {$year}"),
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => ($lang === 'en' ? "Total Year {$year}" : "Total Annuel {$year}"), 'value' => $grandTotPriceFmt . ' MAD', 'color' => '#00C853', 'icon' => 'mdi-cash'],
                ['label' => ($lang === 'en' ? 'Total Fortnight 1 (Q1)' : 'Total Quinzaine 1 (Q1)'), 'value' => $totQ1PriceFmt . ' MAD', 'color' => '#1565C0', 'icon' => 'mdi-calendar-check'],
                ['label' => ($lang === 'en' ? 'Total Fortnight 2 (Q2)' : 'Total Quinzaine 2 (Q2)'), 'value' => $totQ2PriceFmt . ' MAD', 'color' => '#42A5F5', 'icon' => 'mdi-calendar-clock'],
                ['label' => ($lang === 'en' ? 'Total Working Days' : 'Journées Travaillées'), 'value' => $grandTotDaysFmt . ' j', 'color' => '#00897B', 'icon' => 'mdi-account-clock'],
            ],
            'table' => [
                'title' => ($lang === 'en' ? "Fortnight Breakdown by Month - Year {$year}" : "Tableau Détaillé des Quinzaines par Mois - {$year}"),
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($rows) ? [
                'id' => $chartId,
                'title' => $chartTitle,
                'labels' => $chartLabels,
                'multi_series' => true,
                'series' => [
                    ['name' => $seriesNameQ1, 'data' => $q1SeriesData],
                    ['name' => $seriesNameQ2, 'data' => $q2SeriesData],
                ],
                'colors' => ['#1565C0', '#42A5F5'],
                'type' => 'bar',
                'yUnit' => 'MAD',
                'height' => 320,
            ] : null,
            'navButtons' => [
                ['label' => ($lang === 'en' ? 'Fortnight Sheets' : 'Feuilles de Quinzaine'), 'route' => '/fortnight', 'icon' => 'mdi-calendar-month'],
                ['label' => ($lang === 'en' ? 'Workforce Directory' : 'Main d\'œuvre & Ouvriers'), 'route' => '/workforces', 'icon' => 'mdi-account-group'],
            ],
        ]);
    }

    /**
     * Handle Charges / Achats Breakdown by Category.
     */
    private function handleChargesCategoriesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $categories = DB::table('charge_categories as cc')
            ->leftJoin('charges as c', function ($join) {
                $join->on('c.charge_category_id', '=', 'cc.id')
                    ->whereNull('c.deleted_at');
            })
            ->whereNull('cc.deleted_at')
            ->selectRaw('
                cc.id,
                cc.name,
                COUNT(c.id) as count,
                COALESCE(SUM(c.total_weight), 0) as total_weight,
                COALESCE(SUM(c.total_price), 0) as total_price
            ')
            ->groupBy('cc.id', 'cc.name')
            ->orderBy('total_price', 'desc')
            ->get();

        $totPrice = (float) $categories->sum('total_price');
        $totWeight = (float) $categories->sum('total_weight');
        $totCount = (int) $categories->sum('count');
        $catCount = $categories->count();

        $chartLabels = [];
        $chartData = [];
        $tableRowsHtml = '';

        $topCatName = $categories->first()?->name ?? 'N/A';
        $topCatPrice = (float) ($categories->first()?->total_price ?? 0);

        foreach ($categories as $cat) {
            $cName = $cat->name;
            $cP = (float) $cat->total_price;
            $cW = (float) $cat->total_weight;
            $cC = (int) $cat->count;
            $pct = $totPrice > 0 ? round(($cP / $totPrice) * 100, 1) : 0;

            if ($cP > 0) {
                $chartLabels[] = $cName;
                $chartData[] = round($cP, 2);
            }

            $pFmt = $this->formatNumber($cP, 2);
            $wFmt = $this->formatNumber($cW, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 12px 14px; font-weight: 700; color: #42A5F5;">
                <i class="v-icon notranslate mdi mdi-tag-outline text-blue" style="font-size: 15px; margin-right: 6px;"></i>
                {$cName}
              </td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 600;">{$cC}</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 700;">{$wFmt} KG</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 800; color: #42A5F5;">{$pFmt} MAD</td>
              <td style="padding: 12px 14px; text-align: right;">
                <span style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px;">
                  {$pct}%
                </span>
              </td>
              <td style="padding: 12px 14px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/charges">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        // Total row
        $totPFmt = $this->formatNumber($totPrice, 2);
        $totWFmt = $this->formatNumber($totWeight, 2);
        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(66, 165, 245, 0.08); font-weight: 800; border-top: 2px solid rgba(66, 165, 245, 0.3);">
          <td style="padding: 12px 14px; color: #42A5F5;">TOTAL GÉNÉRAL</td>
          <td style="padding: 12px 14px; text-align: right;">{$totCount}</td>
          <td style="padding: 12px 14px; text-align: right;">{$totWFmt} KG</td>
          <td style="padding: 12px 14px; text-align: right; color: #42A5F5; font-size: 14px;">{$totPFmt} MAD</td>
          <td style="padding: 12px 14px; text-align: right;">100%</td>
          <td></td>
        </tr>
TR;

        $answerText = match ($lang) {
            'en' => "Total purchases across all {$catCount} categories amount to {$totPFmt} MAD ({$totWFmt} KG). The highest category is « {$topCatName} » with " . $this->formatNumber($topCatPrice, 2) . " MAD.",
            'es' => "El total de compras en las {$catCount} categorías asciende a {$totPFmt} MAD ({$totWFmt} KG). La categoría principal es « {$topCatName} » con " . $this->formatNumber($topCatPrice, 2) . " MAD.",
            'ar' => "إجمالي المشتريات عبر جميع الفئات الـ {$catCount} يبلغ {$totPFmt} درهم ({$totWFmt} كغ). الفئة الأعلى هي « {$topCatName} » بمبلغ " . $this->formatNumber($topCatPrice, 2) . " درهم.",
            default => "Le montant cumulé des charges s'élève à {$totPFmt} MAD ({$totWFmt} KG) réparties sur {$catCount} catégories. La catégorie dominante est « {$topCatName} » avec " . $this->formatNumber($topCatPrice, 2) . " MAD.",
        };

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Charges & Achats — Répartition par Catégorie',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-truck-delivery',
            'badgeText' => 'Catégories d\'Achats',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Charges', 'value' => $totPFmt . ' MAD', 'color' => '#42A5F5', 'icon' => 'mdi-cash-multiple'],
                ['label' => 'Poids Réceptionné', 'value' => $totWFmt . ' KG', 'color' => '#00897B', 'icon' => 'mdi-scale'],
                ['label' => 'Catégorie Dominante', 'value' => $topCatName, 'color' => '#FFA726', 'icon' => 'mdi-trophy-outline'],
                ['label' => 'Nombre de Catégories', 'value' => $catCount . ' catégories', 'color' => '#AB47BC', 'icon' => 'mdi-format-list-bulleted-type'],
            ],
            'table' => [
                'title' => 'Détails des Charges par Catégorie',
                'headers' => [
                    ['title' => 'Catégorie', 'align' => 'left'],
                    ['title' => 'Réceptions', 'align' => 'right'],
                    ['title' => 'Poids (KG)', 'align' => 'right'],
                    ['title' => 'Total (MAD)', 'align' => 'right'],
                    ['title' => 'Part (%)', 'align' => 'right'],
                    ['title' => 'Action', 'align' => 'center'],
                ],
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Répartition Graphique des Charges par Catégorie (MAD)',
                'labels' => $chartLabels,
                'data' => $chartData,
                'seriesName' => 'Montant (MAD)',
                'type' => 'bar',
                'color' => '#42A5F5',
                'height' => 320,
            ],
            'navButtons' => [
                ['label' => 'Module Achats', 'route' => '/charges', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Gestion Catégories', 'route' => '/charge_category', 'icon' => 'mdi-tag-multiple'],
            ],
        ]);
    }

    /**
     * Handle Shippings / Ventes Breakdown by Category.
     */
    private function handleShippingsCategoriesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $categories = DB::table('shipping_categories as sc')
            ->leftJoin('shippings as s', function ($join) {
                $join->on('s.shipping_category_id', '=', 'sc.id')
                    ->whereNull('s.deleted_at');
            })
            ->whereNull('sc.deleted_at')
            ->selectRaw('
                sc.id,
                sc.name,
                COUNT(s.id) as count,
                COALESCE(SUM(s.total_weight), 0) as total_weight,
                COALESCE(SUM(s.total_price), 0) as total_price
            ')
            ->groupBy('sc.id', 'sc.name')
            ->orderBy('total_price', 'desc')
            ->get();

        $totPrice = (float) $categories->sum('total_price');
        $totWeight = (float) $categories->sum('total_weight');
        $totCount = (int) $categories->sum('count');
        $catCount = $categories->count();

        $chartLabels = [];
        $chartData = [];
        $tableRowsHtml = '';

        $topCatName = $categories->first()?->name ?? 'N/A';
        $topCatPrice = (float) ($categories->first()?->total_price ?? 0);

        foreach ($categories as $cat) {
            $cName = $cat->name;
            $cP = (float) $cat->total_price;
            $cW = (float) $cat->total_weight;
            $cC = (int) $cat->count;
            $pct = $totPrice > 0 ? round(($cP / $totPrice) * 100, 1) : 0;

            if ($cP > 0) {
                $chartLabels[] = $cName;
                $chartData[] = round($cP, 2);
            }

            $pFmt = $this->formatNumber($cP, 2);
            $wFmt = $this->formatNumber($cW, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 12px 14px; font-weight: 700; color: #4643a0;">
                <i class="v-icon notranslate mdi mdi-tag-outline" style="font-size: 15px; margin-right: 6px; color: #4643a0;"></i>
                {$cName}
              </td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 600;">{$cC}</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 700;">{$wFmt} KG</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 800; color: #4643a0;">{$pFmt} MAD</td>
              <td style="padding: 12px 14px; text-align: right;">
                <span style="background: rgba(70, 67, 160, 0.15); color: #4643a0; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px;">
                  {$pct}%
                </span>
              </td>
              <td style="padding: 12px 14px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #4643a0; font-size: 11px; cursor: pointer; border: none;" data-route="/shippings">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $totPFmt = $this->formatNumber($totPrice, 2);
        $totWFmt = $this->formatNumber($totWeight, 2);
        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(70, 67, 160, 0.08); font-weight: 800; border-top: 2px solid rgba(70, 67, 160, 0.3);">
          <td style="padding: 12px 14px; color: #4643a0;">TOTAL GÉNÉRAL</td>
          <td style="padding: 12px 14px; text-align: right;">{$totCount}</td>
          <td style="padding: 12px 14px; text-align: right;">{$totWFmt} KG</td>
          <td style="padding: 12px 14px; text-align: right; color: #4643a0; font-size: 14px;">{$totPFmt} MAD</td>
          <td style="padding: 12px 14px; text-align: right;">100%</td>
          <td></td>
        </tr>
TR;

        $answerText = match ($lang) {
            'en' => "Total sales across all {$catCount} categories amount to {$totPFmt} MAD ({$totWFmt} KG). Top category is « {$topCatName} » with " . $this->formatNumber($topCatPrice, 2) . " MAD.",
            'es' => "El total de ventas en las {$catCount} categorías asciende a {$totPFmt} MAD ({$totWFmt} KG). La categoría líder es « {$topCatName} » con " . $this->formatNumber($topCatPrice, 2) . " MAD.",
            'ar' => "إجمالي المبيعات عبر جميع الفئات الـ {$catCount} يبلغ {$totPFmt} درهم ({$totWFmt} كغ). الفئة الرائدة هي « {$topCatName} » بمبلغ " . $this->formatNumber($topCatPrice, 2) . " درهم.",
            default => "Le chiffre d'affaires cumulé des expéditions s'élève à {$totPFmt} MAD ({$totWFmt} KG) réparties sur {$catCount} catégories. La catégorie dominante est « {$topCatName} » avec " . $this->formatNumber($topCatPrice, 2) . " MAD.",
        };

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Expéditions & Ventes — Répartition par Catégorie',
            'moduleColor' => '#4643a0',
            'moduleIcon' => 'mdi-ship-wheel',
            'badgeText' => 'Catégories de Ventes',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Ventes', 'value' => $totPFmt . ' MAD', 'color' => '#4643a0', 'icon' => 'mdi-cash-multiple'],
                ['label' => 'Poids Expédié', 'value' => $totWFmt . ' KG', 'color' => '#00897B', 'icon' => 'mdi-scale'],
                ['label' => 'Catégorie Leader', 'value' => $topCatName, 'color' => '#FFA726', 'icon' => 'mdi-trophy-outline'],
                ['label' => 'Nombre de Catégories', 'value' => $catCount . ' catégories', 'color' => '#b39ddb', 'icon' => 'mdi-format-list-bulleted-type'],
            ],
            'table' => [
                'title' => 'Détails des Ventes par Catégorie',
                'headers' => [
                    ['title' => 'Catégorie', 'align' => 'left'],
                    ['title' => 'Expéditions', 'align' => 'right'],
                    ['title' => 'Poids (KG)', 'align' => 'right'],
                    ['title' => 'Total (MAD)', 'align' => 'right'],
                    ['title' => 'Part (%)', 'align' => 'right'],
                    ['title' => 'Action', 'align' => 'center'],
                ],
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Répartition Graphique des Ventes par Catégorie (MAD)',
                'labels' => $chartLabels,
                'data' => $chartData,
                'seriesName' => 'Montant (MAD)',
                'type' => 'bar',
                'color' => '#4643a0',
                'height' => 320,
            ],
            'navButtons' => [
                ['label' => 'Module Ventes', 'route' => '/shippings', 'icon' => 'mdi-ship-wheel'],
                ['label' => 'Gestion Catégories', 'route' => '/shipping_category', 'icon' => 'mdi-tag-multiple'],
            ],
        ]);
    }

    /**
     * Handle Productions Breakdown by Category.
     */
    private function handleProductionsCategoriesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $categories = DB::table('production_categories as pc')
            ->leftJoin('productions as p', function ($join) {
                $join->on('p.production_category_id', '=', 'pc.id')
                    ->whereNull('p.deleted_at');
            })
            ->whereNull('pc.deleted_at')
            ->selectRaw('
                pc.id,
                pc.name,
                COUNT(p.id) as count,
                COALESCE(SUM(p.fresh_fish_weight), 0) as fresh_weight,
                COALESCE(SUM(p.frozen_production_weight), 0) as frozen_weight
            ')
            ->groupBy('pc.id', 'pc.name')
            ->orderBy('frozen_weight', 'desc')
            ->get();

        $totFresh = (float) $categories->sum('fresh_weight');
        $totFrozen = (float) $categories->sum('frozen_weight');
        $totCount = (int) $categories->sum('count');
        $catCount = $categories->count();

        $chartLabels = [];
        $chartData = [];
        $tableRowsHtml = '';

        $topCatName = $categories->first()?->name ?? 'N/A';
        $topCatFrozen = (float) ($categories->first()?->frozen_weight ?? 0);

        foreach ($categories as $cat) {
            $cName = $cat->name;
            $cFr = (float) $cat->fresh_weight;
            $cFz = (float) $cat->frozen_weight;
            $cC = (int) $cat->count;
            $yield = $cFr > 0 ? round(($cFz / $cFr) * 100, 1) : 0;

            if ($cFz > 0) {
                $chartLabels[] = $cName;
                $chartData[] = round($cFz, 2);
            }

            $frFmt = $this->formatNumber($cFr, 2);
            $fzFmt = $this->formatNumber($cFz, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 12px 14px; font-weight: 700; color: #00897B;">
                <i class="v-icon notranslate mdi mdi-snowflake" style="font-size: 15px; margin-right: 6px; color: #00897B;"></i>
                {$cName}
              </td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 600;">{$cC}</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 700;">{$frFmt} KG</td>
              <td style="padding: 12px 14px; text-align: right; font-weight: 800; color: #00897B;">{$fzFmt} KG</td>
              <td style="padding: 12px 14px; text-align: right;">
                <span style="background: rgba(0, 137, 123, 0.15); color: #00897B; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px;">
                  {$yield}%
                </span>
              </td>
              <td style="padding: 12px 14px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #00897B; font-size: 11px; cursor: pointer; border: none;" data-route="/production">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $totFrFmt = $this->formatNumber($totFresh, 2);
        $totFzFmt = $this->formatNumber($totFrozen, 2);
        $globalYield = $totFresh > 0 ? round(($totFrozen / $totFresh) * 100, 1) : 0;

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(0, 137, 123, 0.08); font-weight: 800; border-top: 2px solid rgba(0, 137, 123, 0.3);">
          <td style="padding: 12px 14px; color: #00897B;">TOTAL GÉNÉRAL</td>
          <td style="padding: 12px 14px; text-align: right;">{$totCount}</td>
          <td style="padding: 12px 14px; text-align: right;">{$totFrFmt} KG</td>
          <td style="padding: 12px 14px; text-align: right; color: #00897B; font-size: 14px;">{$totFzFmt} KG</td>
          <td style="padding: 12px 14px; text-align: right;">{$globalYield}%</td>
          <td></td>
        </tr>
TR;

        $answerText = match ($lang) {
            'en' => "Total frozen production across all {$catCount} categories is {$totFzFmt} KG from {$totFrFmt} KG fresh fish (Global yield: {$globalYield}%). Top category: « {$topCatName} » with " . $this->formatNumber($topCatFrozen, 2) . " KG.",
            'es' => "La producción congelada total en las {$catCount} categorías es de {$totFzFmt} KG a partir de {$totFrFmt} KG de pescado fresco (Rendimiento global: {$globalYield}%). Categoría líder: « {$topCatName} » con " . $this->formatNumber($topCatFrozen, 2) . " KG.",
            'ar' => "إجمالي الإنتاج المجمد عبر جميع الفئات الـ {$catCount} يبلغ {$totFzFmt} كغ من أصل {$totFrFmt} كغ سمك طازج (المردود العام: {$globalYield}%). الفئة الرائدة: « {$topCatName} » بـ " . $this->formatNumber($topCatFrozen, 2) . " كغ.",
            default => "La production congelée totale s'élève à {$totFzFmt} KG issue de {$totFrFmt} KG de poisson frais (Rendement global: {$globalYield}%). Catégorie dominante: « {$topCatName} » avec " . $this->formatNumber($topCatFrozen, 2) . " KG.",
        };

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Production & Usine — Répartition par Catégorie',
            'moduleColor' => '#00897B',
            'moduleIcon' => 'mdi-factory',
            'badgeText' => 'Catégories de Production',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Production Congelée', 'value' => $totFzFmt . ' KG', 'color' => '#00897B', 'icon' => 'mdi-snowflake'],
                ['label' => 'Poisson Frais Traité', 'value' => $totFrFmt . ' KG', 'color' => '#42A5F5', 'icon' => 'mdi-scale'],
                ['label' => 'Rendement Moyen', 'value' => $globalYield . ' %', 'color' => '#FFA726', 'icon' => 'mdi-percent'],
                ['label' => 'Nombre de Catégories', 'value' => $catCount . ' catégories', 'color' => '#26A69A', 'icon' => 'mdi-format-list-bulleted-type'],
            ],
            'table' => [
                'title' => 'Détails de Production par Catégorie',
                'headers' => [
                    ['title' => 'Catégorie', 'align' => 'left'],
                    ['title' => 'Lots', 'align' => 'right'],
                    ['title' => 'Frais (KG)', 'align' => 'right'],
                    ['title' => 'Congelé (KG)', 'align' => 'right'],
                    ['title' => 'Rendement', 'align' => 'right'],
                    ['title' => 'Action', 'align' => 'center'],
                ],
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Production Congelée par Catégorie (KG)',
                'labels' => $chartLabels,
                'data' => $chartData,
                'seriesName' => 'Poids Congelé (KG)',
                'type' => 'bar',
                'color' => '#00897B',
                'height' => 320,
            ],
            'navButtons' => [
                ['label' => 'Module Production', 'route' => '/production', 'icon' => 'mdi-factory'],
                ['label' => 'Gestion Catégories', 'route' => '/production_category', 'icon' => 'mdi-tag-multiple'],
            ],
        ]);
    }

    /**
     * Handle Charges / Purchases Monthly Breakdown.
     */
    private function handleChargesMonthlyQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: 2026;

        $rows = DB::table('charges')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->selectRaw("
                MONTH(date) as month_num,
                COUNT(*) as count,
                SUM(total_weight) as total_weight,
                SUM(total_price) as total_price
            ")
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get();

        $monthNames = match ($lang) {
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'es' => [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'],
            'ar' => [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'ماي', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت', 9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'],
            default => [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'],
        };

        $chartLabels = [];
        $amountsData = [];
        $totWeight = 0;
        $totPrice = 0;
        $totCount = 0;

        $tableRowsHtml = '';
        foreach ($rows as $r) {
            $mNum = (int)$r->month_num;
            $mName = $monthNames[$mNum] ?? "Month {$mNum}";
            $chartLabels[] = $mName;

            $w = (float)$r->total_weight;
            $pVal = (float)$r->total_price;
            $cnt = (int)$r->count;
            $avg = $w > 0 ? ($pVal / $w) : 0;

            $amountsData[] = round($pVal, 2);

            $totWeight += $w;
            $totPrice += $pVal;
            $totCount += $cnt;

            $wFmt = $this->formatNumber($w, 2);
            $pFmt = $this->formatNumber($pVal, 2);
            $avgFmt = $this->formatNumber($avg, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">
                <i class="v-icon notranslate mdi mdi-calendar-month text-blue" style="font-size: 15px; margin-right: 4px;"></i>
                {$mName}
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$cnt}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$wFmt} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #00E676;">{$pFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$avgFmt} MAD/KG</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/charges">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        // Summary row
        $totAvg = $totWeight > 0 ? ($totPrice / $totWeight) : 0;
        $totWFmt = $this->formatNumber($totWeight, 2);
        $totPFmt = $this->formatNumber($totPrice, 2);
        $totAvgFmt = $this->formatNumber($totAvg, 2);

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(66, 165, 245, 0.12); font-weight: 800; border-top: 2px solid #42A5F5; border-bottom: 2px solid #42A5F5;">
          <td style="padding: 12px; font-weight: 900; color: #42A5F5; font-size: 13px; text-transform: uppercase;">
            <i class="v-icon notranslate mdi mdi-sigma text-blue" style="font-size: 18px; margin-right: 4px;"></i>
            TOTAL {$year}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totCount}</td>
          <td style="padding: 12px; text-align: right; font-weight: 900;">{$totWFmt} KG</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #00E676; font-size: 14px;">{$totPFmt} MAD</td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totAvgFmt} MAD/KG</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #1565C0; font-size: 11px; cursor: pointer; border: none;" data-route="/charges">
              Achats
            </button>
          </td>
        </tr>
TR;

        $headers = [
            ['title' => 'Mois / Month', 'align' => 'left'],
            ['title' => 'Réceptions', 'align' => 'right'],
            ['title' => 'Volume Total (KG)', 'align' => 'right'],
            ['title' => 'Montant Total (MAD)', 'align' => 'right'],
            ['title' => 'Prix Moyen', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => "Achats & Charges - Récapitulatif Mensuel {$year}",
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-truck-delivery',
            'badgeText' => "Achats Mensuels {$year}",
            'userQuestion' => $p['question'],
            'answerText' => "Voici le bilan mensuel des achats et charges pour l'année {$year} : {$totCount} réceptions enregistrées pour un volume global de {$totWFmt} KG et un montant total de {$totPFmt} MAD (Prix moyen global : {$totAvgFmt} MAD/KG).",
            'kpis' => [
                ['label' => "Total Achats {$year}", 'value' => $totPFmt . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-minus'],
                ['label' => "Volume Total", 'value' => $totWFmt . ' KG', 'color' => '#42A5F5', 'icon' => 'mdi-weight-kilogram'],
                ['label' => "Prix Moyen", 'value' => $totAvgFmt . ' MAD/KG', 'color' => '#29B6F6', 'icon' => 'mdi-tag-outline'],
                ['label' => "Total Réceptions", 'value' => (string)$totCount, 'color' => '#26A69A', 'icon' => 'mdi-truck-check'],
            ],
            'table' => [
                'title' => "Bilan Mensuel des Achats - Année {$year}",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($rows) ? [
                'id' => $chartId,
                'title' => "Évolution Mensuelle des Achats - {$year} (MAD)",
                'labels' => $chartLabels,
                'data' => $amountsData,
                'seriesName' => 'Montant (MAD)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Consulter les Charges', 'route' => '/charges', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Nouvelle Réception', 'route' => '/charges/add_charge', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Shippings / Ventes Monthly Breakdown.
     */
    private function handleShippingsMonthlyQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: 2026;

        $rows = DB::table('shippings')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->selectRaw("
                MONTH(date) as month_num,
                COUNT(*) as count,
                SUM(total_weight) as total_weight,
                SUM(total_price) as total_price
            ")
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get();

        $monthNames = match ($lang) {
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'es' => [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'],
            'ar' => [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'ماي', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت', 9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'],
            default => [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'],
        };

        $chartLabels = [];
        $amountsData = [];
        $totWeight = 0;
        $totPrice = 0;
        $totCount = 0;

        $tableRowsHtml = '';
        foreach ($rows as $r) {
            $mNum = (int)$r->month_num;
            $mName = $monthNames[$mNum] ?? "Month {$mNum}";
            $chartLabels[] = $mName;

            $w = (float)$r->total_weight;
            $pVal = (float)$r->total_price;
            $cnt = (int)$r->count;
            $avg = $w > 0 ? ($pVal / $w) : 0;

            $amountsData[] = round($pVal, 2);

            $totWeight += $w;
            $totPrice += $pVal;
            $totCount += $cnt;

            $wFmt = $this->formatNumber($w, 2);
            $pFmt = $this->formatNumber($pVal, 2);
            $avgFmt = $this->formatNumber($avg, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #4643a0;">
                <i class="v-icon notranslate mdi mdi-calendar-month" style="font-size: 15px; margin-right: 4px; color: #4643a0;"></i>
                {$mName}
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$cnt}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$wFmt} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #00E676;">{$pFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$avgFmt} MAD/KG</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #4643a0; font-size: 11px; cursor: pointer; border: none;" data-route="/shippings">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        // Summary row
        $totAvg = $totWeight > 0 ? ($totPrice / $totWeight) : 0;
        $totWFmt = $this->formatNumber($totWeight, 2);
        $totPFmt = $this->formatNumber($totPrice, 2);
        $totAvgFmt = $this->formatNumber($totAvg, 2);

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(70, 67, 160, 0.12); font-weight: 800; border-top: 2px solid #4643a0; border-bottom: 2px solid #4643a0;">
          <td style="padding: 12px; font-weight: 900; color: #4643a0; font-size: 13px; text-transform: uppercase;">
            <i class="v-icon notranslate mdi mdi-sigma" style="font-size: 18px; margin-right: 4px; color: #4643a0;"></i>
            TOTAL {$year}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totCount}</td>
          <td style="padding: 12px; text-align: right; font-weight: 900;">{$totWFmt} KG</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #00E676; font-size: 14px;">{$totPFmt} MAD</td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totAvgFmt} MAD/KG</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #372e7d; font-size: 11px; cursor: pointer; border: none;" data-route="/shippings">
              Ventes
            </button>
          </td>
        </tr>
TR;

        $headers = [
            ['title' => 'Mois / Month', 'align' => 'left'],
            ['title' => 'Expéditions', 'align' => 'right'],
            ['title' => 'Volume Total (KG)', 'align' => 'right'],
            ['title' => 'Chiffre d\'Affaires (MAD)', 'align' => 'right'],
            ['title' => 'Prix Moyen', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => "Ventes & Expéditions - Récapitulatif Mensuel {$year}",
            'moduleColor' => '#4643a0',
            'moduleIcon' => 'mdi-ship-wheel',
            'badgeText' => "Ventes Mensuelles {$year}",
            'userQuestion' => $p['question'],
            'answerText' => "Voici le bilan mensuel des ventes et expéditions pour l'année {$year} : {$totCount} expéditions enregistrées pour un volume global vendu de {$totWFmt} KG et un chiffre d'affaires de {$totPFmt} MAD (Prix moyen global : {$totAvgFmt} MAD/KG).",
            'kpis' => [
                ['label' => "Total Ventes {$year}", 'value' => $totPFmt . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-plus'],
                ['label' => "Volume Total Vendu", 'value' => $totWFmt . ' KG', 'color' => '#4643a0', 'icon' => 'mdi-weight-kilogram'],
                ['label' => "Prix Moyen Vente", 'value' => $totAvgFmt . ' MAD/KG', 'color' => '#b39ddb', 'icon' => 'mdi-tag-outline'],
                ['label' => "Total Expéditions", 'value' => (string)$totCount, 'color' => '#6c66bb', 'icon' => 'mdi-ferry'],
            ],
            'table' => [
                'title' => "Bilan Mensuel des Ventes - Année {$year}",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($rows) ? [
                'id' => $chartId,
                'title' => "Évolution Mensuelle des Ventes - {$year} (MAD)",
                'labels' => $chartLabels,
                'data' => $amountsData,
                'seriesName' => 'Chiffre d\'Affaires (MAD)',
                'type' => 'bar',
                'color' => '#4643a0',
            ] : null,
            'navButtons' => [
                ['label' => 'Consulter les Ventes', 'route' => '/shippings', 'icon' => 'mdi-ship-wheel'],
                ['label' => 'Nouvelle Expédition', 'route' => '/shippings/add_shipping', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Productions Monthly Breakdown.
     */
    private function handleProductionsMonthlyQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: 2026;

        $rows = DB::table('productions')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->selectRaw("
                MONTH(date) as month_num,
                COUNT(*) as count,
                SUM(frozen_production_weight) as frozen_weight,
                SUM(guano_production_weight) as guano_weight,
                SUM(fresh_reception_weight) as fresh_weight,
                AVG(equilibrium) as avg_eq
            ")
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get();

        $monthNames = match ($lang) {
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'es' => [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'],
            'ar' => [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'ماي', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت', 9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'],
            default => [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'],
        };

        $chartLabels = [];
        $frozenData = [];
        $guanoData = [];
        $freshData = [];
        $totFrozen = 0;
        $totGuano = 0;
        $totFresh = 0;
        $totCount = 0;

        $tableRowsHtml = '';
        foreach ($rows as $r) {
            $mNum = (int)$r->month_num;
            $mName = $monthNames[$mNum] ?? "Month {$mNum}";
            $chartLabels[] = $mName;

            $fz = (float)$r->frozen_weight;
            $gn = (float)$r->guano_weight;
            $fr = (float)$r->fresh_weight;
            $cnt = (int)$r->count;
            $eq = (float)$r->avg_eq;

            $frozenData[] = round($fz / 1000, 2); // Tons
            $guanoData[] = round($gn / 1000, 2); // Tons
            $freshData[] = round($fr / 1000, 2); // Tons

            $totFrozen += $fz;
            $totGuano += $gn;
            $totFresh += $fr;
            $totCount += $cnt;

            $fzFmt = $this->formatNumber($fz, 2);
            $gnFmt = $this->formatNumber($gn, 2);
            $frFmt = $this->formatNumber($fr, 2);
            $eqFmt = $this->formatNumber($eq, 2);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #00897B;">
                <i class="v-icon notranslate mdi mdi-calendar-month" style="font-size: 15px; margin-right: 4px; color: #00897B;"></i>
                {$mName}
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$cnt}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$frFmt} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #26A69A;">{$fzFmt} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #66BB6A;">{$gnFmt} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$eqFmt}</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #00897B; font-size: 11px; cursor: pointer; border: none;" data-route="/production">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        // Summary row
        $totFzFmt = $this->formatNumber($totFrozen, 2);
        $totGnFmt = $this->formatNumber($totGuano, 2);
        $totFrFmt = $this->formatNumber($totFresh, 2);

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(0, 137, 123, 0.12); font-weight: 800; border-top: 2px solid #00897B; border-bottom: 2px solid #00897B;">
          <td style="padding: 12px; font-weight: 900; color: #00897B; font-size: 13px; text-transform: uppercase;">
            <i class="v-icon notranslate mdi mdi-sigma" style="font-size: 18px; margin-right: 4px; color: #00897B;"></i>
            TOTAL {$year}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totCount}</td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">{$totFrFmt} KG</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #26A69A; font-size: 13.5px;">{$totFzFmt} KG</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #66BB6A; font-size: 13.5px;">{$totGnFmt} KG</td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">-</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #00695C; font-size: 11px; cursor: pointer; border: none;" data-route="/production">
              Production
            </button>
          </td>
        </tr>
TR;

        $headers = [
            ['title' => 'Mois / Month', 'align' => 'left'],
            ['title' => 'Séries', 'align' => 'right'],
            ['title' => 'Frais Traité (KG)', 'align' => 'right'],
            ['title' => 'Poisson Congelé (KG)', 'align' => 'right'],
            ['title' => 'Guano & Farine (KG)', 'align' => 'right'],
            ['title' => 'Équilibre Moyen', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => "Production & Rendement Usine - Récapitulatif Mensuel {$year}",
            'moduleColor' => '#00897B',
            'moduleIcon' => 'mdi-factory',
            'badgeText' => "Production Mensuelle {$year}",
            'userQuestion' => $p['question'],
            'answerText' => "Voici le récapitulatif mensuel de production usine pour l'année {$year} : {$totCount} séries de production enregistrées, totalisant {$totFzFmt} KG de poisson congelé produit et {$totGnFmt} KG de farine/guano obtenus à partir de {$totFrFmt} KG de matière fraîche.",
            'kpis' => [
                ['label' => "Total Congelé {$year}", 'value' => $totFzFmt . ' KG', 'color' => '#26A69A', 'icon' => 'mdi-snowflake'],
                ['label' => "Total Farine/Guano", 'value' => $totGnFmt . ' KG', 'color' => '#66BB6A', 'icon' => 'mdi-leaf'],
                ['label' => "Frais Traité", 'value' => $totFrFmt . ' KG', 'color' => '#00897B', 'icon' => 'mdi-fish'],
                ['label' => "Séries Opérées", 'value' => (string)$totCount, 'color' => '#80cbc4', 'icon' => 'mdi-cog-sync'],
            ],
            'table' => [
                'title' => "Bilan Mensuel de Production - Année {$year}",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($rows) ? [
                'id' => $chartId,
                'title' => "Production Mensuelle Congelé vs Farine - {$year} (Tonnes)",
                'labels' => $chartLabels,
                'multi_series' => true,
                'series' => [
                    ['name' => 'Poisson Congelé (T)', 'data' => $frozenData],
                    ['name' => 'Farine / Guano (T)', 'data' => $guanoData],
                ],
                'colors' => ['#00897B', '#66BB6A'],
                'type' => 'bar',
                'yUnit' => 'Tonnes',
                'height' => 320,
            ] : null,
            'navButtons' => [
                ['label' => 'Module Production', 'route' => '/production', 'icon' => 'mdi-factory'],
                ['label' => 'Nouvelle Production', 'route' => '/production/add_production', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Workforce Activities grouped by Factory Section.
     */
    private function handleWorkforceSectionsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: 2026;

        $sections = DB::table('sections as s')
            ->leftJoin('fortnight_activities as fa', function($join) use ($year) {
                $join->on('fa.section_id', '=', 's.id')
                    ->whereYear('fa.date', '=', $year)
                    ->whereNull('fa.deleted_at');
            })
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.name')
            ->select(
                's.id',
                's.name',
                DB::raw('COUNT(fa.id) as activities_count'),
                DB::raw('SUM(fa.price) as total_price'),
                DB::raw('COUNT(DISTINCT fa.workforce_id) as workers_count')
            )
            ->havingRaw('activities_count > 0')
            ->orderByDesc('total_price')
            ->get();

        $totPrice = 0;
        $totActs = 0;
        $chartLabels = [];
        $chartData = [];

        $tableRowsHtml = '';
        foreach ($sections as $s) {
            $name = htmlspecialchars($s->name);
            $cnt = (int)$s->activities_count;
            $price = (float)$s->total_price;
            $workers = (int)$s->workers_count;

            $totPrice += $price;
            $totActs += $cnt;

            $chartLabels[] = $name;
            $chartData[] = round($price, 2);

            $priceFmt = $this->formatNumber($price, 2);
            $cntFmt = $this->formatNumber($cnt, 0);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">
                <i class="v-icon notranslate mdi mdi-domain" style="font-size: 15px; margin-right: 4px; color: #42A5F5;"></i>
                {$name}
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$workers}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$cntFmt} j</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #00E676;">{$priceFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
                  Activités
                </button>
              </td>
            </tr>
TR;
        }

        $totPriceFmt = $this->formatNumber($totPrice, 2);
        $totActsFmt = $this->formatNumber($totActs, 0);

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(66, 165, 245, 0.12); font-weight: 800; border-top: 2px solid #42A5F5; border-bottom: 2px solid #42A5F5;">
          <td style="padding: 12px; font-weight: 900; color: #42A5F5; font-size: 13px; text-transform: uppercase;">
            <i class="v-icon notranslate mdi mdi-sigma text-blue" style="font-size: 18px; margin-right: 4px;"></i>
            TOTAL SECTIONS {$year}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">-</td>
          <td style="padding: 12px; text-align: right; font-weight: 900;">{$totActsFmt} j</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #00E676; font-size: 14px;">{$totPriceFmt} MAD</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #1565C0; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
              Feuilles
            </button>
          </td>
        </tr>
TR;

        $headers = [
            ['title' => 'Section Usine', 'align' => 'left'],
            ['title' => 'Ouvriers Actifs', 'align' => 'right'],
            ['title' => 'Journées Travaillées', 'align' => 'right'],
            ['title' => 'Masse Salariale (MAD)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => "Main d'œuvre par Section Usine - Année {$year}",
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-domain',
            'badgeText' => "Sections Usine {$year}",
            'userQuestion' => $p['question'],
            'answerText' => "Voici la ventilation des activités et de la masse salariale par section usine pour l'année {$year} : un total de {$totActsFmt} journées de travail pour une masse salariale globale de {$totPriceFmt} MAD répartie sur " . count($sections) . " sections actives.",
            'kpis' => [
                ['label' => "Masse Salariale Sections", 'value' => $totPriceFmt . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash'],
                ['label' => "Journées Travaillées", 'value' => $totActsFmt . ' j', 'color' => '#42A5F5', 'icon' => 'mdi-calendar-check'],
                ['label' => "Nombre de Sections", 'value' => (string)count($sections), 'color' => '#26A69A', 'icon' => 'mdi-domain'],
            ],
            'table' => [
                'title' => "Détail par Section Usine ({$year})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($sections) ? [
                'id' => $chartId,
                'title' => "Masse Salariale par Section Usine - {$year} (MAD)",
                'labels' => array_slice($chartLabels, 0, 7),
                'data' => array_slice($chartData, 0, 7),
                'seriesName' => 'Montant (MAD)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Feuilles de Quinzaine', 'route' => '/fortnight', 'icon' => 'mdi-calendar-month'],
                ['label' => 'Gestion des Ouvriers', 'route' => '/workforces', 'icon' => 'mdi-account-group'],
            ],
        ]);
    }

    /**
     * Handle Workforce Activities grouped by Corporal / Équipe.
     */
    private function handleWorkforceCorporalsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $lang = $p['lang'];
        $year = $p['year'] ?: 2026;

        $corporals = DB::table('corporals as c')
            ->leftJoin('fortnight_activities as fa', function($join) use ($year) {
                $join->on('fa.corporal_id', '=', 'c.id')
                    ->whereYear('fa.date', '=', $year)
                    ->whereNull('fa.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.full_name')
            ->select(
                'c.id',
                'c.full_name as name',
                DB::raw('COUNT(fa.id) as activities_count'),
                DB::raw('SUM(fa.price) as total_price'),
                DB::raw('COUNT(DISTINCT fa.workforce_id) as workers_count')
            )
            ->havingRaw('activities_count > 0')
            ->orderByDesc('total_price')
            ->get();

        $totPrice = 0;
        $totActs = 0;
        $chartLabels = [];
        $chartData = [];

        $tableRowsHtml = '';
        foreach ($corporals as $c) {
            $name = htmlspecialchars($c->name);
            $cnt = (int)$c->activities_count;
            $price = (float)$c->total_price;
            $workers = (int)$c->workers_count;

            $totPrice += $price;
            $totActs += $cnt;

            $chartLabels[] = $name;
            $chartData[] = round($price, 2);

            $priceFmt = $this->formatNumber($price, 2);
            $cntFmt = $this->formatNumber($cnt, 0);

            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">
                <i class="v-icon notranslate mdi mdi-account-tie" style="font-size: 15px; margin-right: 4px; color: #42A5F5;"></i>
                {$name}
              </td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$workers}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$cntFmt} j</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #00E676;">{$priceFmt} MAD</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
                  Équipe
                </button>
              </td>
            </tr>
TR;
        }

        $totPriceFmt = $this->formatNumber($totPrice, 2);
        $totActsFmt = $this->formatNumber($totActs, 0);

        $tableRowsHtml .= <<<TR
        <tr style="background: rgba(66, 165, 245, 0.12); font-weight: 800; border-top: 2px solid #42A5F5; border-bottom: 2px solid #42A5F5;">
          <td style="padding: 12px; font-weight: 900; color: #42A5F5; font-size: 13px; text-transform: uppercase;">
            <i class="v-icon notranslate mdi mdi-sigma text-blue" style="font-size: 18px; margin-right: 4px;"></i>
            TOTAL CAPORAUX {$year}
          </td>
          <td style="padding: 12px; text-align: right; font-weight: 800;">-</td>
          <td style="padding: 12px; text-align: right; font-weight: 900;">{$totActsFmt} j</td>
          <td style="padding: 12px; text-align: right; font-weight: 900; color: #00E676; font-size: 14px;">{$totPriceFmt} MAD</td>
          <td style="padding: 12px; text-align: center;">
            <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #1565C0; font-size: 11px; cursor: pointer; border: none;" data-route="/fortnight">
              Feuilles
            </button>
          </td>
        </tr>
TR;

        $headers = [
            ['title' => 'Caporal / Équipe', 'align' => 'left'],
            ['title' => 'Ouvriers Actifs', 'align' => 'right'],
            ['title' => 'Journées Travaillées', 'align' => 'right'],
            ['title' => 'Masse Salariale (MAD)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => "Main d'œuvre par Caporal - Année {$year}",
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-account-tie',
            'badgeText' => "Caporaux & Équipes {$year}",
            'userQuestion' => $p['question'],
            'answerText' => "Voici la ventilation des activités et de la masse salariale par caporal pour l'année {$year} : un total de {$totActsFmt} journées de travail pour une masse salariale globale de {$totPriceFmt} MAD répartie sur " . count($corporals) . " équipes actives.",
            'kpis' => [
                ['label' => "Masse Salariale Caporaux", 'value' => $totPriceFmt . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash'],
                ['label' => "Journées Travaillées", 'value' => $totActsFmt . ' j', 'color' => '#42A5F5', 'icon' => 'mdi-calendar-check'],
                ['label' => "Nombre d'Équipes", 'value' => (string)count($corporals), 'color' => '#26A69A', 'icon' => 'mdi-account-tie'],
            ],
            'table' => [
                'title' => "Détail par Caporal ({$year})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($corporals) ? [
                'id' => $chartId,
                'title' => "Masse Salariale par Caporal - {$year} (MAD)",
                'labels' => array_slice($chartLabels, 0, 7),
                'data' => array_slice($chartData, 0, 7),
                'seriesName' => 'Montant (MAD)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Feuilles de Quinzaine', 'route' => '/fortnight', 'icon' => 'mdi-calendar-month'],
                ['label' => 'Gestion des Ouvriers', 'route' => '/workforces', 'icon' => 'mdi-account-group'],
            ],
        ]);
    }

    /**
     * Handle Charges / Achats queries.
     */
    private function handleChargesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $q = DB::table('charges as c')
            ->leftJoin('suppliers as s', 'c.supplier_id', '=', 's.id')
            ->whereNull('c.deleted_at');

        $this->applyTimeFilter($q, 'c.date', $p['timeFrame']);

        $totalCount = (clone $q)->count();
        $totalWeight = (float) (clone $q)->sum('c.total_weight');
        $totalPrice = (float) (clone $q)->sum('c.total_price');
        $avgPrice = $totalWeight > 0 ? ($totalPrice / $totalWeight) : 0;

        // Fetch records if list or specific records requested
        $limit = $p['limit'] ?: 10;
        $records = (clone $q)
            ->select(
                'c.id',
                'c.date',
                'c.reception_tracking_number',
                's.name as supplier_name',
                'c.total_weight',
                'c.total_price',
                'c.allow_invoice'
            )
            ->orderBy('c.date', 'desc')
            ->orderBy('c.id', 'desc')
            ->limit($limit)
            ->get();

        // Top 5 Suppliers for chart
        $topSuppliers = DB::table('charges as c')
            ->join('suppliers as s', 'c.supplier_id', '=', 's.id')
            ->whereNull('c.deleted_at')
            ->groupBy('s.name')
            ->select('s.name', DB::raw('SUM(c.total_weight) as weight'), DB::raw('SUM(c.total_price) as price'))
            ->orderByDesc('weight')
            ->limit(5)
            ->get();

        $chartLabels = [];
        $chartData = [];
        foreach ($topSuppliers as $ts) {
            $chartLabels[] = $ts->name;
            $chartData[] = round(((float)$ts->weight) / 1000, 2); // tons
        }

        // Build localized textual answer
        $answerText = match ($p['lang']) {
            'ar' => "إليك تفاصيل المشتريات والاستلامات: تم تسجيل {$this->formatNumber($totalCount, 0)} استلام بإجمالي وزن {$this->formatNumber($totalWeight, 2)} كغ وبقيمة إجمالية {$this->formatNumber($totalPrice, 2)} درهم.",
            'es' => "Aquí está el análisis de compras y recepciones: se registraron {$this->formatNumber($totalCount, 0)} recepciones con un peso total de {$this->formatNumber($totalWeight, 2)} KG y un importe de {$this->formatNumber($totalPrice, 2)} MAD.",
            'en' => "Here is the purchases and receptions analysis: {$this->formatNumber($totalCount, 0)} receptions recorded with a total weight of {$this->formatNumber($totalWeight, 2)} KG and a total value of {$this->formatNumber($totalPrice, 2)} MAD.",
            default => "Voici l'analyse détaillée des charges et réceptions : {$this->formatNumber($totalCount, 0)} réceptions enregistrées pour un volume global de {$this->formatNumber($totalWeight, 2)} KG et un montant de {$this->formatNumber($totalPrice, 2)} MAD (Prix moyen : {$this->formatNumber($avgPrice, 2)} MAD/KG).",
        };

        $tableRowsHtml = '';
        foreach ($records as $r) {
            $supp = htmlspecialchars($r->supplier_name ?: 'Fournisseur #' . $r->id);
            $recNum = htmlspecialchars($r->reception_tracking_number ?: ('#' . $r->id));
            $weight = $this->formatNumber((float)$r->total_weight, 2);
            $price = $this->formatNumber((float)$r->total_price, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$recNum}</td>
              <td style="padding: 10px 12px;">{$r->date}</td>
              <td style="padding: 10px 12px; font-weight: 600;">{$supp}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$weight} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$price} MAD</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/charges">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'N° Réception', 'align' => 'left'],
            ['title' => 'Date', 'align' => 'left'],
            ['title' => 'Fournisseur', 'align' => 'left'],
            ['title' => 'Poids (KG)', 'align' => 'right'],
            ['title' => 'Montant (MAD)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Achats & Réceptions (Charges)',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-truck-delivery',
            'badgeText' => $p['timeFrame'] === 'all' ? 'Toutes Périodes' : ucfirst($p['timeFrame']),
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Réceptions', 'value' => $this->formatNumber($totalCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-counter'],
                ['label' => 'Poids Total', 'value' => $this->formatNumber($totalWeight, 2) . ' KG', 'color' => '#29B6F6', 'icon' => 'mdi-weight-kilogram'],
                ['label' => 'Montant Global', 'value' => $this->formatNumber($totalPrice, 2) . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-multiple'],
                ['label' => 'Prix Moyen / KG', 'value' => $this->formatNumber($avgPrice, 2) . ' MAD', 'color' => '#FFA726', 'icon' => 'mdi-scale-balance'],
            ],
            'table' => [
                'title' => "Dernières Charges & Réceptions (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($chartLabels) ? [
                'id' => $chartId,
                'title' => 'Top 5 Fournisseurs par Volume (Tonnes)',
                'labels' => $chartLabels,
                'data' => $chartData,
                'seriesName' => 'Volume (Tonnes)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Consulter les Charges', 'route' => '/charges', 'icon' => 'mdi-format-list-bulleted'],
                ['label' => 'Nouvelle Charge', 'route' => '/charges/add_charge', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Shippings / Ventes queries.
     */
    private function handleShippingsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $q = DB::table('shippings as s')
            ->leftJoin('clients as c', 's.client_id', '=', 'c.id')
            ->whereNull('s.deleted_at');

        $this->applyTimeFilter($q, 's.date', $p['timeFrame']);

        $totalCount = (clone $q)->count();
        $totalWeight = (float) (clone $q)->sum('s.total_weight');
        $totalPrice = (float) (clone $q)->sum('s.total_price');
        $avgPrice = $totalWeight > 0 ? ($totalPrice / $totalWeight) : 0;

        $limit = $p['limit'] ?: 10;
        $records = (clone $q)
            ->select(
                's.id',
                's.date',
                's.shipping_tracking_number',
                'c.name as client_name',
                's.direction',
                's.total_weight',
                's.total_price'
            )
            ->orderBy('s.date', 'desc')
            ->orderBy('s.id', 'desc')
            ->limit($limit)
            ->get();

        // Top 5 Clients
        $topClients = DB::table('shippings as s')
            ->join('clients as c', 's.client_id', '=', 'c.id')
            ->whereNull('s.deleted_at')
            ->groupBy('c.name')
            ->select('c.name', DB::raw('SUM(s.total_price) as price'), DB::raw('SUM(s.total_weight) as weight'))
            ->orderByDesc('price')
            ->limit(5)
            ->get();

        $chartLabels = [];
        $chartData = [];
        foreach ($topClients as $tc) {
            $chartLabels[] = $tc->name;
            $chartData[] = round(((float)$tc->price) / 1000, 2); // k MAD
        }

        $answerText = match ($p['lang']) {
            'ar' => "إليك تحليل المبيعات والشحنات: تم تسجيل {$this->formatNumber($totalCount, 0)} شحنة بقيمة إجمالية قدرها {$this->formatNumber($totalPrice, 2)} درهم وبإجمالي وزن {$this->formatNumber($totalWeight, 2)} كغ.",
            'es' => "Aquí está el análisis de ventas y expediciones: se registraron {$this->formatNumber($totalCount, 0)} expediciones por un valor de {$this->formatNumber($totalPrice, 2)} MAD y un peso de {$this->formatNumber($totalWeight, 2)} KG.",
            'en' => "Here is the sales and shipping analysis: {$this->formatNumber($totalCount, 0)} shipments recorded for a total revenue of {$this->formatNumber($totalPrice, 2)} MAD and {$this->formatNumber($totalWeight, 2)} KG.",
            default => "Voici l'analyse des ventes et expéditions : {$this->formatNumber($totalCount, 0)} expéditions enregistrées, représentant un chiffre d'affaires de {$this->formatNumber($totalPrice, 2)} MAD et un tonnage total de {$this->formatNumber($totalWeight, 2)} KG (Prix moyen de vente : {$this->formatNumber($avgPrice, 2)} MAD/KG).",
        };

        $tableRowsHtml = '';
        foreach ($records as $r) {
            $client = htmlspecialchars($r->client_name ?: 'Client #' . $r->id);
            $shipNum = htmlspecialchars($r->shipping_tracking_number ?: ('#' . $r->id));
            $weight = $this->formatNumber((float)$r->total_weight, 2);
            $price = $this->formatNumber((float)$r->total_price, 2);
            $dir = htmlspecialchars($r->direction ?: 'Local');
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #b39ddb;">{$shipNum}</td>
              <td style="padding: 10px 12px;">{$r->date}</td>
              <td style="padding: 10px 12px; font-weight: 600;">{$client}</td>
              <td style="padding: 10px 12px;"><span style="background: rgba(108,102,187,0.15); color: #b39ddb; padding: 2px 8px; border-radius: 12px; font-size: 11px;">{$dir}</span></td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$weight} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$price} MAD</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #4643a0; font-size: 11px; cursor: pointer; border: none;" data-route="/shippings">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'N° Expédition', 'align' => 'left'],
            ['title' => 'Date', 'align' => 'left'],
            ['title' => 'Client', 'align' => 'left'],
            ['title' => 'Direction', 'align' => 'left'],
            ['title' => 'Poids (KG)', 'align' => 'right'],
            ['title' => 'Montant (MAD)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Ventes & Expéditions (Shippings)',
            'moduleColor' => '#4643a0',
            'moduleIcon' => 'mdi-ship-wheel',
            'badgeText' => 'Expéditions Clients',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Expéditions', 'value' => $this->formatNumber($totalCount, 0), 'color' => '#b39ddb', 'icon' => 'mdi-send-check'],
                ['label' => 'Volume Expédié', 'value' => $this->formatNumber($totalWeight, 2) . ' KG', 'color' => '#9575cd', 'icon' => 'mdi-weight-kilogram'],
                ['label' => 'Chiffre d\'Affaires', 'value' => $this->formatNumber($totalPrice, 2) . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-multiple'],
                ['label' => 'Prix Moyen Vente', 'value' => $this->formatNumber($avgPrice, 2) . ' MAD', 'color' => '#ffb74d', 'icon' => 'mdi-chart-line'],
            ],
            'table' => [
                'title' => "Dernières Expéditions Clients (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($chartLabels) ? [
                'id' => $chartId,
                'title' => 'Top 5 Clients par Chiffre d\'Affaires (k MAD)',
                'labels' => $chartLabels,
                'data' => $chartData,
                'seriesName' => 'Montant (k MAD)',
                'type' => 'bar',
                'color' => '#4643a0',
            ] : null,
            'navButtons' => [
                ['label' => 'Consulter les Ventes', 'route' => '/shippings', 'icon' => 'mdi-format-list-bulleted'],
                ['label' => 'Nouvelle Vente', 'route' => '/shippings/add_shipping', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Productions queries.
     */
    private function handleProductionsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $q = DB::table('productions')->whereNull('deleted_at');
        $this->applyTimeFilter($q, 'date', $p['timeFrame']);

        $totalCount = (clone $q)->count();
        $totalWeight = (float) (clone $q)->sum('total_weight');
        $freshWeight = (float) (clone $q)->sum('fresh_reception_weight');
        $frozenWeight = (float) (clone $q)->sum('frozen_production_weight');
        $guanoWeight = (float) (clone $q)->sum('guano_production_weight');

        $limit = $p['limit'] ?: 10;
        $records = (clone $q)
            ->select('id', 'date', 'production_type', 'fresh_reception_weight', 'frozen_production_weight', 'guano_production_weight', 'equilibrium')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $answerText = "Voici le récapitulatif de production usine : {$this->formatNumber($totalCount, 0)} opérations de production enregistrées, avec un total de {$this->formatNumber($frozenWeight, 2)} KG de poisson congelé, {$this->formatNumber($guanoWeight, 2)} KG de farine/guano et {$this->formatNumber($freshWeight, 2)} KG de réception poisson frais.";

        $tableRowsHtml = '';
        foreach ($records as $r) {
            $frozen = $this->formatNumber((float)$r->frozen_production_weight, 2);
            $guano = $this->formatNumber((float)$r->guano_production_weight, 2);
            $fresh = $this->formatNumber((float)$r->fresh_reception_weight, 2);
            $eq = $this->formatNumber((float)$r->equilibrium, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #00897B;">#{$r->id}</td>
              <td style="padding: 10px 12px;">{$r->date}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$fresh} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #26A69A;">{$frozen} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #66BB6A;">{$guano} KG</td>
              <td style="padding: 10px 12px; text-align: right;">{$eq}</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #00897B; font-size: 11px; cursor: pointer; border: none;" data-route="/production">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'ID', 'align' => 'left'],
            ['title' => 'Date', 'align' => 'left'],
            ['title' => 'Frais (KG)', 'align' => 'right'],
            ['title' => 'Congelé (KG)', 'align' => 'right'],
            ['title' => 'Guano/Farine (KG)', 'align' => 'right'],
            ['title' => 'Équilibre', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Production & Rendement Usine',
            'moduleColor' => '#00897B',
            'moduleIcon' => 'mdi-factory',
            'badgeText' => 'Usine & Transformation',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Productions', 'value' => $this->formatNumber($totalCount, 0), 'color' => '#00897B', 'icon' => 'mdi-cog-sync'],
                ['label' => 'Poisson Congelé', 'value' => $this->formatNumber($frozenWeight, 2) . ' KG', 'color' => '#26A69A', 'icon' => 'mdi-snowflake'],
                ['label' => 'Production Guano', 'value' => $this->formatNumber($guanoWeight, 2) . ' KG', 'color' => '#66BB6A', 'icon' => 'mdi-leaf'],
                ['label' => 'Frais Traité', 'value' => $this->formatNumber($freshWeight, 2) . ' KG', 'color' => '#80cbc4', 'icon' => 'mdi-fish'],
            ],
            'table' => [
                'title' => "Dernières Séries de Production (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Répartition de la Production par Type (Tonnes)',
                'labels' => ['Poisson Congelé', 'Guano & Farine', 'Poisson Frais'],
                'data' => [round($frozenWeight / 1000, 2), round($guanoWeight / 1000, 2), round($freshWeight / 1000, 2)],
                'seriesName' => 'Volume (Tonnes)',
                'type' => 'pie',
                'color' => '#00897B',
            ],
            'navButtons' => [
                ['label' => 'Consulter la Production', 'route' => '/production', 'icon' => 'mdi-format-list-bulleted'],
                ['label' => 'Nouvelle Production', 'route' => '/production/add_production', 'icon' => 'mdi-plus-circle'],
            ],
        ]);
    }

    /**
     * Handle Warehouses & Stocks queries.
     */
    private function handleWarehousesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $keyword = $p['keyword'];

        if ($keyword) {
            // User asked about specific fish stock (e.g. sardine)
            $stockRows = DB::table('warehouse_balances as wb')
                ->join('fish_types as ft', 'wb.stockable_id', '=', 'ft.id')
                ->join('warehouses as w', 'wb.warehouse_id', '=', 'w.id')
                ->where('ft.name_fr', 'LIKE', "%{$keyword}%")
                ->whereNull('wb.deleted_at')
                ->groupBy('ft.name_fr', 'w.name')
                ->select(
                    'ft.name_fr as species_name',
                    'w.name as warehouse_name',
                    DB::raw('SUM(wb.remaining_weight) as stock_kg'),
                    DB::raw('SUM(wb.total_price) as stock_val')
                )
                ->havingRaw('stock_kg > 0')
                ->orderByDesc('stock_kg')
                ->get();

            $totalSpeciesStock = (float) $stockRows->sum('stock_kg');
            $totalSpeciesVal = (float) $stockRows->sum('stock_val');

            $answerText = "Voici la situation des stocks pour l'espèce « " . ucfirst($keyword) . " » : un stock total disponible de {$this->formatNumber($totalSpeciesStock, 2)} KG valorisé à {$this->formatNumber($totalSpeciesVal, 2)} MAD réparti sur " . count($stockRows) . " magasin(s).";

            $tableRowsHtml = '';
            foreach ($stockRows as $sr) {
                $kg = $this->formatNumber((float)$sr->stock_kg, 2);
                $val = $this->formatNumber((float)$sr->stock_val, 2);
                $tableRowsHtml .= <<<TR
                <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
                  <td style="padding: 10px 12px; font-weight: 700; color: #a21ee5;">{$sr->species_name}</td>
                  <td style="padding: 10px 12px; font-weight: 600;">{$sr->warehouse_name}</td>
                  <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$kg} KG</td>
                  <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$val} MAD</td>
                  <td style="padding: 10px 12px; text-align: center;">
                    <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #a21ee5; font-size: 11px; cursor: pointer; border: none;" data-route="/warehouses">
                      Gérer
                    </button>
                  </td>
                </tr>
TR;
            }

            $headers = [
                ['title' => 'Espèce / Produit', 'align' => 'left'],
                ['title' => 'Entrepôt / Magasin', 'align' => 'left'],
                ['title' => 'Stock Disponible (KG)', 'align' => 'right'],
                ['title' => 'Valeur Estimée (MAD)', 'align' => 'right'],
                ['title' => 'Action', 'align' => 'center'],
            ];

            return $this->renderStandardCard([
                'uniqueId' => $uniqueId,
                'chartId' => $chartId,
                'theme' => $theme,
                'moduleTitle' => 'Stock Espèce: ' . ucfirst($keyword),
                'moduleColor' => '#a21ee5',
                'moduleIcon' => 'mdi-warehouse',
                'badgeText' => 'Stock Espèce Filtré',
                'userQuestion' => $p['question'],
                'answerText' => $answerText,
                'kpis' => [
                    ['label' => 'Stock Disponible', 'value' => $this->formatNumber($totalSpeciesStock, 2) . ' KG', 'color' => '#a21ee5', 'icon' => 'mdi-weight-kilogram'],
                    ['label' => 'Valeur Estimée', 'value' => $this->formatNumber($totalSpeciesVal, 2) . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-multiple'],
                    ['label' => 'Nombre d\'Entrepôts', 'value' => (string)count($stockRows), 'color' => '#9742f5', 'icon' => 'mdi-warehouse'],
                ],
                'table' => [
                    'title' => "Détail du Stock « {$keyword} » par Entrepôt",
                    'headers' => $headers,
                    'rowsHtml' => $tableRowsHtml,
                ],
                'chart' => count($stockRows) ? [
                    'id' => $chartId,
                    'title' => "Répartition du Stock {$keyword} par Entrepôt (KG)",
                    'labels' => $stockRows->pluck('warehouse_name')->toArray(),
                    'data' => $stockRows->map(fn($r) => round((float)$r->stock_kg, 2))->toArray(),
                    'seriesName' => 'Stock (KG)',
                    'type' => 'pie',
                    'color' => '#a21ee5',
                ] : null,
                'navButtons' => [
                    ['label' => 'Gestion des Entrepôts', 'route' => '/warehouses', 'icon' => 'mdi-warehouse'],
                    ['label' => 'Fiche Espèces', 'route' => '/parameters/fish-management', 'icon' => 'mdi-fish'],
                ],
            ]);
        }

        // Global Warehouses Stock overview
        $warehouses = DB::table('warehouses as w')
            ->whereNull('w.deleted_at')
            ->select('w.id', 'w.name', 'w.warehouse_type', 'w.stock_type', 'w.total_weight')
            ->orderByDesc('w.total_weight')
            ->get();

        $totalStockKg = (float) $warehouses->sum('total_weight');
        $warehousesCount = count($warehouses);

        $answerText = "Voici la situation globale des entrepôts et stocks : le réseau compte {$warehousesCount} magasins avec un stock global cumulé de {$this->formatNumber($totalStockKg, 2)} KG.";

        $tableRowsHtml = '';
        foreach ($warehouses as $wh) {
            $kg = $this->formatNumber((float)$wh->total_weight, 2);
            $type = htmlspecialchars($wh->warehouse_type ?: 'Standard');
            $stockType = htmlspecialchars($wh->stock_type ?: 'company');
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #a21ee5;">{$wh->name}</td>
              <td style="padding: 10px 12px;">{$type}</td>
              <td style="padding: 10px 12px;"><span style="background: rgba(162,30,229,0.15); color: #a21ee5; padding: 2px 8px; border-radius: 12px; font-size: 11px;">{$stockType}</span></td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$kg} KG</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #a21ee5; font-size: 11px; cursor: pointer; border: none;" data-route="/warehouses">
                  Consulter
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Entrepôt', 'align' => 'left'],
            ['title' => 'Type Magasin', 'align' => 'left'],
            ['title' => 'Catégorie Stock', 'align' => 'left'],
            ['title' => 'Poids Total (KG)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Entrepôts & Stocks (Warehouses)',
            'moduleColor' => '#a21ee5',
            'moduleIcon' => 'mdi-warehouse',
            'badgeText' => 'Situation des Stocks',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Magasins', 'value' => (string)$warehousesCount, 'color' => '#a21ee5', 'icon' => 'mdi-warehouse'],
                ['label' => 'Stock Global', 'value' => $this->formatNumber($totalStockKg, 2) . ' KG', 'color' => '#00E676', 'icon' => 'mdi-weight-kilogram'],
            ],
            'table' => [
                'title' => "Situation des Magasins & Chambres Froides",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($warehouses) ? [
                'id' => $chartId,
                'title' => 'Top Magasins par Volume (KG)',
                'labels' => $warehouses->take(6)->pluck('name')->toArray(),
                'data' => $warehouses->take(6)->map(fn($w) => round((float)$w->total_weight, 2))->toArray(),
                'seriesName' => 'Poids (KG)',
                'type' => 'bar',
                'color' => '#a21ee5',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion des Entrepôts', 'route' => '/warehouses', 'icon' => 'mdi-warehouse'],
                ['label' => 'Transferts Inter-Magasins', 'route' => '/warehouse_transfer', 'icon' => 'mdi-swap-horizontal'],
            ],
        ]);
    }

    /**
     * Handle Invoices & Finances queries.
     */
    private function handleInvoicesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $clientInvoicesCount = DB::table('client_invoices')->whereNull('deleted_at')->count();
        $clientInvoicesTotal = (float) DB::table('client_invoices')->whereNull('deleted_at')->sum('total_price');

        $supplierInvoicesCount = DB::table('supplier_invoices')->whereNull('deleted_at')->count();
        $supplierInvoicesTotal = (float) DB::table('supplier_invoices')->whereNull('deleted_at')->sum('total_price');

        $limit = $p['limit'] ?: 10;
        $invoices = DB::table('client_invoices as ci')
            ->leftJoin('clients as c', 'ci.client_id', '=', 'c.id')
            ->whereNull('ci.deleted_at')
            ->select('ci.id', 'ci.date', 'c.name as client_name', 'ci.total_price', 'ci.total_weight', 'ci.type_invoice')
            ->orderBy('ci.date', 'desc')
            ->limit($limit)
            ->get();

        $answerText = "Voici la situation de la facturation : {$this->formatNumber($clientInvoicesCount, 0)} factures clients émises ({$this->formatNumber($clientInvoicesTotal, 2)} MAD) et {$this->formatNumber($supplierInvoicesCount, 0)} factures fournisseurs enregistrées ({$this->formatNumber($supplierInvoicesTotal, 2)} MAD).";

        $tableRowsHtml = '';
        foreach ($invoices as $inv) {
            $client = htmlspecialchars($inv->client_name ?: 'Client #' . $inv->id);
            $price = $this->formatNumber((float)$inv->total_price, 2);
            $weight = $this->formatNumber((float)$inv->total_weight, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #E65100;">#FAC-{$inv->id}</td>
              <td style="padding: 10px 12px;">{$inv->date}</td>
              <td style="padding: 10px 12px; font-weight: 600;">{$client}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 600;">{$weight} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$price} MAD</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #E65100; font-size: 11px; cursor: pointer; border: none;" data-route="/client_invoice">
                  Voir
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'N° Facture', 'align' => 'left'],
            ['title' => 'Date', 'align' => 'left'],
            ['title' => 'Client', 'align' => 'left'],
            ['title' => 'Poids (KG)', 'align' => 'right'],
            ['title' => 'Montant (MAD)', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Facturation & Finances',
            'moduleColor' => '#E65100',
            'moduleIcon' => 'mdi-file-document-outline',
            'badgeText' => 'Factures & Règlements',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Factures Clients', 'value' => $this->formatNumber($clientInvoicesCount, 0), 'color' => '#E65100', 'icon' => 'mdi-receipt-text'],
                ['label' => 'Total Facturé Clients', 'value' => $this->formatNumber($clientInvoicesTotal, 2) . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash-plus'],
                ['label' => 'Factures Fournisseurs', 'value' => $this->formatNumber($supplierInvoicesCount, 0), 'color' => '#F57C00', 'icon' => 'mdi-receipt-text-outline'],
                ['label' => 'Total Facturé Fournisseurs', 'value' => $this->formatNumber($supplierInvoicesTotal, 2) . ' MAD', 'color' => '#FF7043', 'icon' => 'mdi-cash-minus'],
            ],
            'table' => [
                'title' => "Dernières Factures Émises (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Volume Financier Facturé (MAD)',
                'labels' => ['Factures Clients (Ventes)', 'Factures Fournisseurs (Achats)'],
                'data' => [round($clientInvoicesTotal, 2), round($supplierInvoicesTotal, 2)],
                'seriesName' => 'Montant (MAD)',
                'type' => 'pie',
                'color' => '#E65100',
            ],
            'navButtons' => [
                ['label' => 'Factures Clients', 'route' => '/client_invoice', 'icon' => 'mdi-receipt'],
                ['label' => 'Factures Fournisseurs', 'route' => '/supplier_invoice', 'icon' => 'mdi-receipt-text'],
            ],
        ]);
    }

    /**
     * Handle Workforce & Fortnight queries.
     */
    private function handleWorkforceQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $workersCount = DB::table('workforces')->whereNull('deleted_at')->count();
        $activitiesCount = DB::table('fortnight_activities')->whereNull('deleted_at')->count();
        $totalPaid = (float) DB::table('fortnight_activities')->whereNull('deleted_at')->sum('price');

        $sections = DB::table('sections as s')
            ->leftJoin('fortnight_activities as fa', 'fa.section_id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.name')
            ->select('s.name', DB::raw('COUNT(fa.id) as activities_count'), DB::raw('SUM(fa.price) as total_price'))
            ->orderByDesc('activities_count')
            ->get();

        $limit = $p['limit'] ?: 10;
        $workers = DB::table('workforces')
            ->whereNull('deleted_at')
            ->select('id', 'full_name', 'matricule', 'daily_price', 'status', 'nationality')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $answerText = "Voici la situation de la main d'œuvre : {$this->formatNumber($workersCount, 0)} ouvriers enregistrés, avec un historique de {$this->formatNumber($activitiesCount, 0)} journées de travail réparties sur les quinzaines pour une masse salariale traitée de {$this->formatNumber($totalPaid, 2)} MAD.";

        $tableRowsHtml = '';
        foreach ($workers as $w) {
            $name = htmlspecialchars($w->full_name);
            $mat = htmlspecialchars($w->matricule ?: ('#' . $w->id));
            $wage = $this->formatNumber((float)$w->daily_price, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$mat}</td>
              <td style="padding: 10px 12px; font-weight: 600;">{$name}</td>
              <td style="padding: 10px 12px;">{$w->nationality}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$wage} MAD/J</td>
              <td style="padding: 10px 12px; text-align: center;">
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1 text-white" style="background: #42A5F5; font-size: 11px; cursor: pointer; border: none;" data-route="/workforces">
                  Fiche
                </button>
              </td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Matricule', 'align' => 'left'],
            ['title' => 'Nom Complet', 'align' => 'left'],
            ['title' => 'Nationalité', 'align' => 'left'],
            ['title' => 'Salaire Journalier', 'align' => 'right'],
            ['title' => 'Action', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Module Main d\'œuvre & Quinzaine',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-account-group',
            'badgeText' => 'Personnel & Quinzaine',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Ouvriers', 'value' => $this->formatNumber($workersCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-account-multiple'],
                ['label' => 'Total Journées / Activités', 'value' => $this->formatNumber($activitiesCount, 0), 'color' => '#29B6F6', 'icon' => 'mdi-calendar-check'],
                ['label' => 'Masse Salariale', 'value' => $this->formatNumber($totalPaid, 2) . ' MAD', 'color' => '#00E676', 'icon' => 'mdi-cash'],
            ],
            'table' => [
                'title' => "Liste des Ouvriers Récents (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($sections) ? [
                'id' => $chartId,
                'title' => 'Activités par Section Usine',
                'labels' => $sections->take(5)->pluck('name')->toArray(),
                'data' => $sections->take(5)->pluck('activities_count')->toArray(),
                'seriesName' => 'Activités',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion de la Main d\'œuvre', 'route' => '/workforces', 'icon' => 'mdi-account-group'],
                ['label' => 'Feuilles de Quinzaine', 'route' => '/fortnight', 'icon' => 'mdi-calendar-month'],
            ],
        ]);
    }

    /**
     * Handle Trucks queries.
     */
    private function handleTrucksQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $trucksCount = DB::table('trucks')->whereNull('deleted_at')->count();
        $limit = $p['limit'] ?: 10;

        $trucks = DB::table('trucks as t')
            ->leftJoin('charge_truck as ct', 'ct.truck_id', '=', 't.id')
            ->leftJoin('shipping_truck as st', 'st.truck_id', '=', 't.id')
            ->whereNull('t.deleted_at')
            ->groupBy('t.id', 't.registration_plate')
            ->select(
                't.id',
                't.registration_plate',
                DB::raw('COUNT(DISTINCT ct.charge_id) as charge_trips'),
                DB::raw('COUNT(DISTINCT st.shipping_id) as shipping_trips')
            )
            ->orderByRaw('(COUNT(DISTINCT ct.charge_id) + COUNT(DISTINCT st.shipping_id)) DESC')
            ->limit($limit)
            ->get();

        $answerText = "Voici la situation de la flotte de camions : {$this->formatNumber($trucksCount, 0)} camions répertoriés dans la base de données.";

        $tableRowsHtml = '';
        foreach ($trucks as $t) {
            $plate = htmlspecialchars($t->registration_plate ?: 'Camion #' . $t->id);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$plate}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 600;">{$t->charge_trips}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 600;">{$t->shipping_trips}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 700; color: #00E676;">" . ($t->charge_trips + $t->shipping_trips) . "</td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Matricule Camion', 'align' => 'left'],
            ['title' => 'Voyages Réceptions', 'align' => 'center'],
            ['title' => 'Voyages Expéditions', 'align' => 'center'],
            ['title' => 'Total Missions', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Flotte Camions & Transport',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-truck',
            'badgeText' => 'Logistique & Transport',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Camions', 'value' => $this->formatNumber($trucksCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-truck'],
            ],
            'table' => [
                'title' => "Camions les Plus Actifs (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($trucks) ? [
                'id' => $chartId,
                'title' => 'Top Camions par Nombre de Missions',
                'labels' => $trucks->take(5)->pluck('registration_plate')->toArray(),
                'data' => $trucks->take(5)->map(fn($t) => $t->charge_trips + $t->shipping_trips)->toArray(),
                'seriesName' => 'Missions',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion Camions', 'route' => '/parameters/trucks', 'icon' => 'mdi-truck'],
            ],
        ]);
    }

    /**
     * Handle Ships queries.
     */
    private function handleShipsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $shipsCount = DB::table('ships')->whereNull('deleted_at')->count();
        $limit = $p['limit'] ?: 10;

        $ships = DB::table('ships as s')
            ->leftJoin('charges as c', 'c.ship_id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.name', 's.imo_number')
            ->select('s.id', 's.name', 's.imo_number', DB::raw('COUNT(c.id) as landings_count'), DB::raw('SUM(c.total_weight) as total_weight'))
            ->orderByDesc('landings_count')
            ->limit($limit)
            ->get();

        $answerText = "Voici la flotte maritime : {$this->formatNumber($shipsCount, 0)} navires et bateaux de pêche répertoriés.";

        $tableRowsHtml = '';
        foreach ($ships as $s) {
            $name = htmlspecialchars($s->name ?: 'Navire #' . $s->id);
            $imo = htmlspecialchars($s->imo_number ?: '-');
            $weight = $this->formatNumber((float)$s->total_weight, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$name}</td>
              <td style="padding: 10px 12px;">{$imo}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 600;">{$s->landings_count}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$weight} KG</td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Nom du Navire', 'align' => 'left'],
            ['title' => 'N° IMO / Immatriculation', 'align' => 'left'],
            ['title' => 'Débarquements', 'align' => 'center'],
            ['title' => 'Poids Total Débarqué (KG)', 'align' => 'right'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Flotte Maritime & Navires',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-ferry',
            'badgeText' => 'Bateaux & Débarquements',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Navires', 'value' => $this->formatNumber($shipsCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-ferry'],
            ],
            'table' => [
                'title' => "Navires les Plus Actifs (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($ships) ? [
                'id' => $chartId,
                'title' => 'Top Navires par Nombre de Débarquements',
                'labels' => $ships->take(5)->pluck('name')->toArray(),
                'data' => $ships->take(5)->pluck('landings_count')->toArray(),
                'seriesName' => 'Débarquements',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion Navires', 'route' => '/parameters/ships', 'icon' => 'mdi-ferry'],
            ],
        ]);
    }

    /**
     * Handle Fish Types & Taxonomy queries.
     */
    private function handleFishTypesQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $typesCount = DB::table('fish_types')->whereNull('deleted_at')->count();
        $limit = $p['limit'] ?: 10;

        $types = DB::table('fish_types')
            ->whereNull('deleted_at')
            ->select('id', 'name_fr', 'name_en', 'name_es', 'scientific_name', 'type')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $answerText = "Voici la nomenclature des espèces de poissons : {$this->formatNumber($typesCount, 0)} types de poissons et conditionnements configurés dans le système.";

        $tableRowsHtml = '';
        foreach ($types as $t) {
            $fr = htmlspecialchars($t->name_fr ?: '-');
            $en = htmlspecialchars($t->name_en ?: '-');
            $es = htmlspecialchars($t->name_es ?: '-');
            $sci = htmlspecialchars($t->scientific_name ?: '-');
            $typeBadge = $t->type === 'fresh' ? '<span style="background: rgba(0,137,123,0.15); color: #00897B; padding: 2px 8px; border-radius: 12px; font-size: 11px;">Frais</span>' : '<span style="background: rgba(33,150,243,0.15); color: #42A5F5; padding: 2px 8px; border-radius: 12px; font-size: 11px;">Congelé</span>';
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$fr}</td>
              <td style="padding: 10px 12px;">{$en}</td>
              <td style="padding: 10px 12px;">{$es}</td>
              <td style="padding: 10px 12px; font-style: italic;">{$sci}</td>
              <td style="padding: 10px 12px; text-align: center;">{$typeBadge}</td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Nom (Français)', 'align' => 'left'],
            ['title' => 'Nom (Anglais)', 'align' => 'left'],
            ['title' => 'Nom (Espagnol)', 'align' => 'left'],
            ['title' => 'Nom Scientifique', 'align' => 'left'],
            ['title' => 'Type', 'align' => 'center'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Nomenclature des Espèces de Poissons',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-fish',
            'badgeText' => 'Taxonomie Poissons',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Espèces & Articles', 'value' => $this->formatNumber($typesCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-fish'],
            ],
            'table' => [
                'title' => "Espèces Répertoriées (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'navButtons' => [
                ['label' => 'Gestion des Espèces', 'route' => '/parameters/fish-management', 'icon' => 'mdi-fish'],
            ],
        ]);
    }

    /**
     * Handle Suppliers queries.
     */
    private function handleSuppliersQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $suppliersCount = DB::table('suppliers')->whereNull('deleted_at')->count();
        $limit = $p['limit'] ?: 10;

        $topSuppliers = DB::table('suppliers as s')
            ->leftJoin('charges as c', 'c.supplier_id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.name', 's.phone', 's.city')
            ->select('s.id', 's.name', 's.phone', 's.city', DB::raw('COUNT(c.id) as charges_count'), DB::raw('SUM(c.total_weight) as total_weight'), DB::raw('SUM(c.total_price) as total_price'))
            ->orderByDesc('total_weight')
            ->limit($limit)
            ->get();

        $answerText = "Voici la liste des fournisseurs de poissons : {$this->formatNumber($suppliersCount, 0)} fournisseurs actifs répertoriés.";

        $tableRowsHtml = '';
        foreach ($topSuppliers as $s) {
            $name = htmlspecialchars($s->name);
            $city = htmlspecialchars($s->city ?: '-');
            $weight = $this->formatNumber((float)$s->total_weight, 2);
            $price = $this->formatNumber((float)$s->total_price, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #42A5F5;">{$name}</td>
              <td style="padding: 10px 12px;">{$city}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 600;">{$s->charges_count}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$weight} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$price} MAD</td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Fournisseur', 'align' => 'left'],
            ['title' => 'Ville', 'align' => 'left'],
            ['title' => 'Réceptions', 'align' => 'center'],
            ['title' => 'Poids Total (KG)', 'align' => 'right'],
            ['title' => 'Montant Total (MAD)', 'align' => 'right'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Répertoire des Fournisseurs',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-account-tie',
            'badgeText' => 'Fournisseurs',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Fournisseurs', 'value' => $this->formatNumber($suppliersCount, 0), 'color' => '#42A5F5', 'icon' => 'mdi-account-tie'],
            ],
            'table' => [
                'title' => "Top Fournisseurs par Volume (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($topSuppliers) ? [
                'id' => $chartId,
                'title' => 'Top 5 Fournisseurs par Volume (Tonnes)',
                'labels' => $topSuppliers->take(5)->pluck('name')->toArray(),
                'data' => $topSuppliers->take(5)->map(fn($s) => round((float)$s->total_weight / 1000, 2))->toArray(),
                'seriesName' => 'Volume (Tonnes)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion Fournisseurs', 'route' => '/suppliers', 'icon' => 'mdi-account-tie'],
            ],
        ]);
    }

    /**
     * Handle Clients queries.
     */
    private function handleClientsQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $clientsCount = DB::table('clients')->whereNull('deleted_at')->count();
        $limit = $p['limit'] ?: 10;

        $topClients = DB::table('clients as c')
            ->leftJoin('shippings as s', 's.client_id', '=', 'c.id')
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.name', 'c.phone', 'c.city')
            ->select('c.id', 'c.name', 'c.phone', 'c.city', DB::raw('COUNT(s.id) as shippings_count'), DB::raw('SUM(s.total_weight) as total_weight'), DB::raw('SUM(s.total_price) as total_price'))
            ->orderByDesc('total_price')
            ->limit($limit)
            ->get();

        $answerText = "Voici la liste des clients et partenaires : {$this->formatNumber($clientsCount, 0)} clients enregistrés.";

        $tableRowsHtml = '';
        foreach ($topClients as $c) {
            $name = htmlspecialchars($c->name);
            $city = htmlspecialchars($c->city ?: '-');
            $weight = $this->formatNumber((float)$c->total_weight, 2);
            $price = $this->formatNumber((float)$c->total_price, 2);
            $tableRowsHtml .= <<<TR
            <tr style="border-bottom: 1px solid var(--ai-border, rgba(255,255,255,0.06));">
              <td style="padding: 10px 12px; font-weight: 700; color: #4643a0;">{$name}</td>
              <td style="padding: 10px 12px;">{$city}</td>
              <td style="padding: 10px 12px; text-align: center; font-weight: 600;">{$c->shippings_count}</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700;">{$weight} KG</td>
              <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #00E676;">{$price} MAD</td>
            </tr>
TR;
        }

        $headers = [
            ['title' => 'Client', 'align' => 'left'],
            ['title' => 'Ville', 'align' => 'left'],
            ['title' => 'Expéditions', 'align' => 'center'],
            ['title' => 'Volume Acheté (KG)', 'align' => 'right'],
            ['title' => 'Chiffre d\'Affaires (MAD)', 'align' => 'right'],
        ];

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Répertoire des Clients',
            'moduleColor' => '#4643a0',
            'moduleIcon' => 'mdi-account-star',
            'badgeText' => 'Clients & Acheteurs',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Clients', 'value' => $this->formatNumber($clientsCount, 0), 'color' => '#4643a0', 'icon' => 'mdi-account-star'],
            ],
            'table' => [
                'title' => "Top Clients par Chiffre d'Affaires (Top {$limit})",
                'headers' => $headers,
                'rowsHtml' => $tableRowsHtml,
            ],
            'chart' => count($topClients) ? [
                'id' => $chartId,
                'title' => 'Top 5 Clients par Chiffre d\'Affaires (k MAD)',
                'labels' => $topClients->take(5)->pluck('name')->toArray(),
                'data' => $topClients->take(5)->map(fn($c) => round((float)$c->total_price / 1000, 2))->toArray(),
                'seriesName' => 'Montant (k MAD)',
                'type' => 'bar',
                'color' => '#4643a0',
            ] : null,
            'navButtons' => [
                ['label' => 'Gestion Clients', 'route' => '/clients', 'icon' => 'mdi-account-star'],
            ],
        ]);
    }

    /**
     * Handle Comparison (Achats vs Ventes) queries.
     */
    private function handleComparisonQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $chargesWeight = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_weight');
        $chargesPrice = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_price');

        $shippingsWeight = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_weight');
        $shippingsPrice = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_price');

        $margin = $shippingsPrice - $chargesPrice;

        $answerText = "Voici le bilan comparatif Achats vs Ventes : Volume acheté : {$this->formatNumber($chargesWeight, 2)} KG ({$this->formatNumber($chargesPrice, 2)} MAD) | Volume vendu : {$this->formatNumber($shippingsWeight, 2)} KG ({$this->formatNumber($shippingsPrice, 2)} MAD). Marge brute d'activité : {$this->formatNumber($margin, 2)} MAD.";

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Comparatif Achats (Charges) vs Ventes (Expéditions)',
            'moduleColor' => '#42A5F5',
            'moduleIcon' => 'mdi-scale-balance',
            'badgeText' => 'Bilan & Rentabilité',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Achats (Charges)', 'value' => $this->formatNumber($chargesPrice, 2) . ' MAD', 'color' => '#42A5F5', 'icon' => 'mdi-cash-minus'],
                ['label' => 'Ventes (Shippings)', 'value' => $this->formatNumber($shippingsPrice, 2) . ' MAD', 'color' => '#4643a0', 'icon' => 'mdi-cash-plus'],
                ['label' => 'Volume Acheté', 'value' => $this->formatNumber($chargesWeight, 2) . ' KG', 'color' => '#29B6F6', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Volume Vendu', 'value' => $this->formatNumber($shippingsWeight, 2) . ' KG', 'color' => '#b39ddb', 'icon' => 'mdi-ship-wheel'],
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Comparaison Achats vs Ventes (Millions MAD)',
                'labels' => ['Achats (Charges)', 'Ventes (Expéditions)'],
                'data' => [round($chargesPrice / 1000000, 2), round($shippingsPrice / 1000000, 2)],
                'seriesName' => 'Montant (M MAD)',
                'type' => 'bar',
                'color' => '#42A5F5',
            ],
            'navButtons' => [
                ['label' => 'Consulter les Charges', 'route' => '/charges', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Consulter les Ventes', 'route' => '/shippings', 'icon' => 'mdi-ship-wheel'],
            ],
        ]);
    }

    /**
     * Handle Generic Search Query across tables.
     */
    private function handleGenericSearchQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $q = $p['question'];
        return $this->handleOverviewQuery($uniqueId, $chartId, $p, $theme);
    }

    /**
     * Handle Overview Query.
     */
    private function handleOverviewQuery(string $uniqueId, string $chartId, array $p, string $theme): string
    {
        $chargesCount = DB::table('charges')->whereNull('deleted_at')->count();
        $chargesWeight = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_weight');
        $chargesPrice = (float) DB::table('charges')->whereNull('deleted_at')->sum('total_price');

        $shippingsCount = DB::table('shippings')->whereNull('deleted_at')->count();
        $shippingsWeight = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_weight');
        $shippingsPrice = (float) DB::table('shippings')->whereNull('deleted_at')->sum('total_price');

        $prodFrozenWeight = (float) DB::table('productions')->whereNull('deleted_at')->sum('frozen_production_weight');
        $totalStockKg = (float) DB::table('warehouses')->whereNull('deleted_at')->sum('total_weight');

        $answerText = "Voici la synthèse globale du système ERP : {$this->formatNumber($chargesCount, 0)} réceptions d'achats ({$this->formatNumber($chargesWeight, 2)} KG), {$this->formatNumber($shippingsCount, 0)} expéditions de ventes ({$this->formatNumber($shippingsWeight, 2)} KG), {$this->formatNumber($prodFrozenWeight, 2)} KG congelés produits, et un stock actuel en magasin de {$this->formatNumber($totalStockKg, 2)} KG.";

        return $this->renderStandardCard([
            'uniqueId' => $uniqueId,
            'chartId' => $chartId,
            'theme' => $theme,
            'moduleTitle' => 'Tableau de Bord Exécutif & Indicateurs Clés',
            'moduleColor' => '#1565C0',
            'moduleIcon' => 'mdi-view-dashboard-outline',
            'badgeText' => 'Vue d\'Ensemble ERP',
            'userQuestion' => $p['question'],
            'answerText' => $answerText,
            'kpis' => [
                ['label' => 'Total Achats (Charges)', 'value' => $this->formatNumber($chargesPrice, 2) . ' MAD', 'color' => '#42A5F5', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Total Ventes (Shippings)', 'value' => $this->formatNumber($shippingsPrice, 2) . ' MAD', 'color' => '#4643a0', 'icon' => 'mdi-ship-wheel'],
                ['label' => 'Production Congelée', 'value' => $this->formatNumber($prodFrozenWeight, 2) . ' KG', 'color' => '#00897B', 'icon' => 'mdi-snowflake'],
                ['label' => 'Stock en Magasins', 'value' => $this->formatNumber($totalStockKg, 2) . ' KG', 'color' => '#a21ee5', 'icon' => 'mdi-warehouse'],
            ],
            'chart' => [
                'id' => $chartId,
                'title' => 'Volumes Clés du Système (Tonnes)',
                'labels' => ['Achats Réceptionnés', 'Ventes Expédiées', 'Production Congelée', 'Stock en Entrepôts'],
                'data' => [
                    round($chargesWeight / 1000, 2),
                    round($shippingsWeight / 1000, 2),
                    round($prodFrozenWeight / 1000, 2),
                    round($totalStockKg / 1000, 2),
                ],
                'seriesName' => 'Volume (Tonnes)',
                'type' => 'bar',
                'color' => '#1565C0',
            ],
            'navButtons' => [
                ['label' => 'Module Achats', 'route' => '/charges', 'icon' => 'mdi-truck-delivery'],
                ['label' => 'Module Ventes', 'route' => '/shippings', 'icon' => 'mdi-ship-wheel'],
                ['label' => 'Module Production', 'route' => '/production', 'icon' => 'mdi-factory'],
                ['label' => 'Module Entrepôts', 'route' => '/warehouses', 'icon' => 'mdi-warehouse'],
            ],
        ]);
    }

    // =========================================================================
    //  RENDER HELPER
    // =========================================================================

    /**
     * Unified card renderer for rich, responsive Vue/HTML output with ApexCharts and navigation.
     */
    private function renderStandardCard(array $cfg): string
    {
        $uniqueId = $cfg['uniqueId'];
        $chartId = $cfg['chartId'] ?? '';
        $theme = $cfg['theme'] ?? 'dark';
        $isDark = ($theme === 'dark');

        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';
        $cardBorder = $isDark ? 'rgba(66, 165, 245, 0.25)' : 'rgba(0, 0, 0, 0.08)';
        $innerBorder = $isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

        $normalizeIcon = function (?string $icon): string {
            if (!$icon) return 'mdi mdi-circle-outline';
            $name = trim($icon);
            $name = preg_replace('/^(?:v-icon\s+|notranslate\s+|mdi\s+)+/i', '', $name);
            $name = preg_replace('/^mdi-/i', '', $name);
            return 'mdi mdi-' . trim($name);
        };

        $modColor = $cfg['moduleColor'] ?? '#42A5F5';
        $modTitle = htmlspecialchars($cfg['moduleTitle'] ?? 'Jupiter AI Engine');
        $modIcon = $normalizeIcon($cfg['moduleIcon'] ?? 'mdi-robot');
        $badgeText = htmlspecialchars($cfg['badgeText'] ?? 'Analyse Précise');
        $userQuestion = htmlspecialchars($cfg['userQuestion'] ?? '');
        $answerText = htmlspecialchars($cfg['answerText'] ?? '');

        // 1. KPI Cards HTML
        $kpiHtml = '';
        if (!empty($cfg['kpis'])) {
            $kpiHtml .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;">';
            foreach ($cfg['kpis'] as $kpi) {
                $kVal = htmlspecialchars($kpi['value']);
                $kLbl = htmlspecialchars($kpi['label']);
                $kCol = $kpi['color'] ?? $modColor;
                $kIco = $normalizeIcon($kpi['icon'] ?? 'mdi-chart-line');
                $kpiHtml .= <<<KPI
                <div class="ai-analytics-kpi" style="background: var(--ai-inner-bg, {$innerBg}); border-radius: 14px; padding: 15px 18px; border: 1px solid var(--ai-border, {$innerBorder}); box-shadow: 0 4px 14px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 14px; position: relative; overflow: hidden;">
                  <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: {$kCol}; border-radius: 4px 0 0 4px;"></div>
                  <div style="width: 44px; height: 44px; border-radius: 12px; background: {$kCol}18; border: 1px solid {$kCol}30; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="{$kIco}" style="font-size: 22px; color: {$kCol};"></i>
                  </div>
                  <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--ai-sub-color, {$subColor}); text-transform: uppercase; letter-spacing: 0.6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{$kLbl}</div>
                    <div style="font-size: 18.5px; font-weight: 800; color: {$kCol}; margin-top: 2px; font-variant-numeric: tabular-nums;">{$kVal}</div>
                  </div>
                </div>
KPI;
            }
            $kpiHtml .= '</div>';
        }

        // 2. Table HTML
        $tableHtml = '';
        if (!empty($cfg['table'])) {
            $tTitle = htmlspecialchars($cfg['table']['title'] ?? 'Détails des Enregistrements');
            $tHeaders = '';
            foreach ($cfg['table']['headers'] as $h) {
                $align = $h['align'] ?? 'left';
                $tHeaders .= "<th style=\"padding: 10px 12px; text-align: {$align};\">{$h['title']}</th>";
            }
            $tRows = $cfg['table']['rowsHtml'] ?? '';
            $tableHtml = <<<TBL
            <div class="ai-analytics-inner" style="background: var(--ai-inner-bg, {$innerBg}); border-radius: 16px; padding: 16px; margin-bottom: 20px; border: 1px solid var(--ai-border, {$innerBorder});">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 13px; font-weight: 800; color: {$modColor}; text-transform: uppercase; letter-spacing: 0.6px; display: flex; align-items: center; gap: 6px;">
                  <i class="v-icon notranslate mdi mdi-table" style="font-size: 16px;"></i>
                  {$tTitle}
                </div>
              </div>
              <div style="overflow-x: auto; width: 100%;">
                <table class="ai-analytics-table" style="width: 100%; border-collapse: collapse; font-size: 12.5px; color: var(--ai-text-color, {$textColor});">
                  <thead>
                    <tr style="border-bottom: 2px solid rgba(66, 165, 245, 0.2); color: var(--ai-sub-color, {$subColor}); font-size: 11.5px; text-transform: uppercase;">
                      {$tHeaders}
                    </tr>
                  </thead>
                  <tbody>
                    {$tRows}
                  </tbody>
                </table>
              </div>
            </div>
TBL;
        }

        // 3. Chart HTML & JS
        $chartContainerHtml = '';
        $chartJs = '';
        if (!empty($cfg['chart'])) {
            $c = $cfg['chart'];
            $cTitle = htmlspecialchars($c['title'] ?? 'Visualisation Graphique');
            $chartHeight = $c['height'] ?? 280;
            $chartContainerHtml = <<<CHART
            <div class="ai-analytics-inner" style="background: var(--ai-inner-bg, {$innerBg}); border-radius: 16px; padding: 16px; margin-bottom: 20px; border: 1px solid var(--ai-border, {$innerBorder});">
              <div style="font-size: 13px; font-weight: 800; color: {$modColor}; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                <i class="v-icon notranslate mdi mdi-chart-bell-curve-cumulative" style="font-size: 16px;"></i>
                {$cTitle}
              </div>
              <div id="{$c['id']}" style="min-height: {$chartHeight}px;"></div>
            </div>
CHART;

            $labelsJson = json_encode($c['labels'] ?? []);
            $dataJson = json_encode($c['data'] ?? []);
            $sName = addslashes($c['seriesName'] ?? 'Valeur');
            $cType = $c['type'] ?? 'bar';
            $cCol = $c['color'] ?? $modColor;

            if ($cType === 'pie' || $cType === 'donut') {
                $chartJs = <<<JS
(function(){
  var chartEl = document.querySelector('#{$c['id']}');
  if (!chartEl || !window.ApexCharts) return;
  var options = {
    chart: { type: '{$cType}', height: {$chartHeight}, id: '{$c['id']}', background: 'transparent' },
    theme: { mode: '{$theme}' },
    series: {$dataJson},
    labels: {$labelsJson},
    colors: ['#42A5F5', '#00897B', '#b39ddb', '#FFA726', '#EF5350', '#26A69A'],
    legend: { position: 'bottom', labels: { colors: '{$subColor}' } },
    dataLabels: { enabled: true },
    tooltip: { theme: '{$theme}' }
  };
  var chart = new ApexCharts(chartEl, options);
  chart.render();
  window.__ai_charts = window.__ai_charts || {};
  window.__ai_charts['{$c['id']}'] = chart;
})();
JS;
            } elseif (!empty($c['multi_series'])) {
                $seriesJson = json_encode($c['series'] ?? []);
                $colorsJson = json_encode($c['colors'] ?? ['#42A5F5', '#FFB74D', '#00E676', '#E65100']);
                $yFormatterUnit = addslashes($c['yUnit'] ?? 'MAD');
                $chartJs = <<<JS
(function(){
  var chartEl = document.querySelector('#{$c['id']}');
  if (!chartEl || !window.ApexCharts) return;
  var options = {
    chart: { 
      type: '{$cType}', 
      height: {$chartHeight}, 
      id: '{$c['id']}', 
      background: 'transparent',
      toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } }
    },
    theme: { mode: '{$theme}' },
    series: {$seriesJson},
    xaxis: { 
      categories: {$labelsJson}, 
      labels: { style: { colors: '{$subColor}', fontSize: '11px', fontWeight: 600 } } 
    },
    yaxis: { 
      labels: { 
        style: { colors: '{$subColor}', fontSize: '11px' },
        formatter: function(val) {
          if (val >= 1000000) return (val / 1000000).toFixed(1) + ' M';
          if (val >= 1000) return (val / 1000).toFixed(0) + ' k';
          return val;
        }
      } 
    },
    colors: {$colorsJson},
    plotOptions: { 
      bar: { 
        borderRadius: 6, 
        horizontal: false, 
        columnWidth: '55%' 
      } 
    },
    legend: { 
      show: true, 
      position: 'top', 
      horizontalAlign: 'right', 
      labels: { colors: '{$textColor}' },
      markers: { radius: 12 }
    },
    grid: { 
      borderColor: '{$innerBorder}',
      strokeDashArray: 4
    },
    dataLabels: { enabled: false },
    tooltip: { 
      theme: '{$theme}',
      y: {
        formatter: function(val) {
          return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val) + ' {$yFormatterUnit}';
        }
      }
    }
  };
  var chart = new ApexCharts(chartEl, options);
  chart.render();
  window.__ai_charts = window.__ai_charts || {};
  window.__ai_charts['{$c['id']}'] = chart;
})();
JS;
            } else {
                $chartJs = <<<JS
(function(){
  var chartEl = document.querySelector('#{$c['id']}');
  if (!chartEl || !window.ApexCharts) return;
  var options = {
    chart: { type: 'bar', height: {$chartHeight}, id: '{$c['id']}', background: 'transparent', toolbar: { show: false } },
    theme: { mode: '{$theme}' },
    series: [{ name: '{$sName}', data: {$dataJson} }],
    xaxis: { categories: {$labelsJson}, labels: { style: { colors: '{$subColor}', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '{$subColor}', fontSize: '11px' } } },
    colors: ['{$cCol}'],
    plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '45%' } },
    grid: { borderColor: '{$innerBorder}' },
    dataLabels: { enabled: false },
    tooltip: { theme: '{$theme}' }
  };
  var chart = new ApexCharts(chartEl, options);
  chart.render();
  window.__ai_charts = window.__ai_charts || {};
  window.__ai_charts['{$c['id']}'] = chart;
})();
JS;
            }
        }

        // 4. Navigation Buttons
        $navBtnsHtml = '';
        if (!empty($cfg['navButtons'])) {
            foreach ($cfg['navButtons'] as $btn) {
                $bLbl = htmlspecialchars($btn['label']);
                $bRt = htmlspecialchars($btn['route']);
                $bIco = $normalizeIcon($btn['icon'] ?? 'mdi-arrow-right');
                $navBtnsHtml .= <<<BTN
                <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-3 py-1" style="background: {$modColor}20; color: {$modColor}; font-weight: 700; border: 1px solid {$modColor}50; cursor: pointer;" data-route="{$bRt}">
                  <i class="v-icon notranslate {$bIco}" style="font-size: 14px; margin-right: 4px;"></i>
                  {$bLbl}
                </button>
BTN;
            }
        }

        return <<<HTML
<div id="{$uniqueId}">
  <div class="ai-analytics-card" style="background: var(--ai-card-bg, {$cardBg}); border-radius: 20px; padding: 22px; border: 1px solid var(--ai-border, {$cardBorder}); box-shadow: 0 10px 30px rgba(0,0,0,0.12); margin-bottom: 8px;">
    <!-- Top Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--ai-border, {$innerBorder}); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: {$modColor}; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px {$modColor}40; flex-shrink: 0;">
          <i class="{$modIcon} text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: {$modColor};">{$modTitle}</div>
          <div style="font-size: 11.5px; color: var(--ai-sub-color, {$subColor}); font-weight: 600;">Question: <strong style="color: var(--ai-text-color, {$textColor});">« {$userQuestion} »</strong></div>
        </div>
      </div>
      <span style="background: {$modColor}18; color: {$modColor}; border: 1px solid {$modColor}40; padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 5px;">
        <i class="mdi mdi-check-decagram" style="font-size: 13px;"></i>
        {$badgeText}
      </span>
    </div>

    <!-- Direct Answer Banner -->
    <div style="font-size: 14px; line-height: 1.7; color: var(--ai-text-color, {$textColor}); background: {$modColor}12; padding: 14px 18px; border-radius: 12px; border-left: 4px solid {$modColor}; margin-bottom: 20px; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: {$modColor}; letter-spacing: 0.8px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
        <i class="mdi mdi-checkbox-marked-circle-outline" style="font-size: 14px;"></i>
        Réponse Directe
      </div>
      {$answerText}
    </div>

    {$kpiHtml}
    {$tableHtml}
    {$chartContainerHtml}

    <!-- Bottom Actions / Shortcuts -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 11.5px; color: var(--ai-sub-color, {$subColor}); border-top: 1px solid var(--ai-border, {$innerBorder}); padding-top: 16px; margin-top: 4px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <i class="mdi mdi-shield-check" style="font-size: 16px; color: {$modColor};"></i>
        <span>Jupiter ERP Intelligence • Conçu et supervisé par <strong>ELBACHIR NOUBAA</strong></span>
      </div>
      <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        {$navBtnsHtml}
      </div>
    </div>
  </div>
</div>
<script>
{$chartJs}
</script>
HTML;
    }

    /**
     * Apply time filters to query builder.
     */
    private function applyTimeFilter($q, string $column, string $timeFrame): void
    {
        if (str_starts_with($timeFrame, 'year_')) {
            $y = (int) substr($timeFrame, 5);
            if ($y > 2000) {
                $q->whereYear($column, $y);
                return;
            }
        }

        switch ($timeFrame) {
            case 'today':
                $q->whereDate($column, DB::raw('CURDATE()'));
                break;
            case 'yesterday':
                $q->whereDate($column, DB::raw('SUBDATE(CURDATE(), 1)'));
                break;
            case 'this_week':
                $q->whereDate($column, '>=', DB::raw('SUBDATE(CURDATE(), INTERVAL 7 DAY)'));
                break;
            case 'this_month':
                $q->whereRaw("MONTH({$column}) = MONTH(CURDATE()) AND YEAR({$column}) = YEAR(CURDATE())");
                break;
            case 'last_month':
                $q->whereRaw("MONTH({$column}) = MONTH(SUBDATE(CURDATE(), INTERVAL 1 MONTH)) AND YEAR({$column}) = YEAR(SUBDATE(CURDATE(), INTERVAL 1 MONTH))");
                break;
            case 'year_2026':
                $q->whereRaw("YEAR({$column}) = 2026");
                break;
            case 'year_2025':
                $q->whereRaw("YEAR({$column}) = 2025");
                break;
            case 'year_2024':
                $q->whereRaw("YEAR({$column}) = 2024");
                break;
        }
    }

    /**
     * Generates Creator & Supervisor response honoring ELBACHIR NOUBAA.
     */
    public function generateCreatorResponse(string $uniqueId, string $lang, string $theme): string
    {
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        return <<<HTML
<div id="{$uniqueId}">
  <div class="ai-analytics-card" style="background: var(--ai-card-bg, {$cardBg}); border-radius: 20px; padding: 24px; border: 1px solid rgba(66, 165, 245, 0.35); box-shadow: 0 10px 30px rgba(21, 101, 192, 0.15); margin-bottom: 8px;">
    <!-- Creator Badge Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid rgba(66, 165, 245, 0.2); padding-bottom: 16px;">
      <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 52px; height: 52px; border-radius: 16px; background: linear-gradient(135deg, #1565C0 0%, #1E88E5 50%, #42A5F5 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 16px rgba(21, 101, 192, 0.4);">
          <i class="v-icon notranslate mdi mdi-crown text-white" style="font-size: 28px;"></i>
        </div>
        <div>
          <div style="font-size: 20px; font-weight: 800; color: #42A5F5; letter-spacing: 0.5px;">ELBACHIR NOUBAA</div>
          <div style="font-size: 13px; font-weight: 600; color: var(--ai-sub-color, {$subColor}); display: flex; align-items: center; gap: 6px;">
            <i class="v-icon notranslate mdi mdi-shield-check text-blue" style="font-size: 16px; color: #42A5F5 !important;"></i>
            <span>Architecte Concepteur & Superviseur Technique de Jupiter ERP</span>
          </div>
        </div>
      </div>
      <span style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; border: 1px solid rgba(66, 165, 245, 0.35); padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
        Fondateur & Concepteur
      </span>
    </div>

    <!-- Description -->
    <div style="font-size: 14px; line-height: 1.8; color: var(--ai-text-color, {$textColor}); margin-bottom: 20px;">
      Cet agent d'intelligence décisionnelle et l'ensemble de l'écosystème <strong>Jupiter ERP</strong> ont été conçus, structurés et optimisés sous la supervision directe de <strong>ELBACHIR NOUBAA</strong>. Le moteur assure la gouvernance de données temps réel pour la gestion de l'industrie de congélation, pêche maritime, transformation et logistique internationale.
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 11.5px; color: var(--ai-sub-color, {$subColor}); border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 14px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <i class="v-icon notranslate mdi mdi-check-decagram" style="font-size: 16px; color: #42A5F5;"></i>
        <span>Architecture Certifiée ELBACHIR NOUBAA</span>
      </div>
      <button class="ai-nav-btn v-btn v-btn--variant-elevated v-btn--size-small rounded-pill px-4 py-1" style="background: linear-gradient(135deg, #1565C0, #42A5F5); color: white; font-weight: 700; border: none; cursor: pointer;" data-route="/dashboard">
        <i class="v-icon notranslate mdi mdi-view-dashboard" style="font-size: 14px; margin-right: 6px;"></i>
        Tableau de Bord
      </button>
    </div>
  </div>
</div>
HTML;
    }

    /**
     * Generates analysis for uploaded Excel files.
     */
    public function generateExcelAnalysisResponse(string $uniqueId, string $chartId, string $question, array $excelFiles, string $lang, string $theme): string
    {
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

        $previewRows = array_slice($excelFiles[0]['rows'] ?? [], 0, 8);
        $isDark = ($theme === 'dark');
        $cardBg = $isDark ? '#1a2332' : '#ffffff';
        $innerBg = $isDark ? 'rgba(255,255,255,0.04)' : '#f8fafc';
        $textColor = $isDark ? '#e2e8f0' : '#1e293b';
        $subColor = $isDark ? '#94a3b8' : '#64748b';

        $thHtml = '';
        foreach (array_slice($headers, 0, 6) as $h) {
            $thHtml .= "<th style=\"padding: 8px 10px; text-align: left;\">{$h}</th>";
        }

        $trHtml = '';
        foreach ($previewRows as $r) {
            $trHtml .= "<tr style=\"border-bottom: 1px solid rgba(255,255,255,0.06);\">";
            foreach (array_slice($headers, 0, 6) as $h) {
                $val = is_scalar($r[$h] ?? null) ? $r[$h] : (is_array($r) && isset($r[array_search($h, $headers)]) ? $r[array_search($h, $headers)] : '');
                $trHtml .= "<td style=\"padding: 8px 10px; color: var(--ai-text-color, {$textColor});\">{$val}</td>";
            }
            $trHtml .= "</tr>";
        }

        return <<<HTML
<div id="{$uniqueId}">
  <div class="ai-analytics-card" style="background: var(--ai-card-bg, {$cardBg}); border-radius: 20px; padding: 22px; border: 1px solid rgba(76, 175, 80, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 8px;">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid rgba(76, 175, 80, 0.2); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #1B5E20, #2E7D32, #4CAF50); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(46, 125, 50, 0.35);">
          <i class="v-icon notranslate mdi mdi-file-excel-outline text-white" style="font-size: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 17px; font-weight: 800; color: #4CAF50;">Analyse Fichier Excel : {$firstFileName}</div>
          <div style="font-size: 11.5px; color: var(--ai-sub-color, {$subColor}); font-weight: 600;">Question : <strong style="color: var(--ai-text-color, {$textColor});">« {$question} »</strong></div>
        </div>
      </div>
      <span style="background: rgba(76, 175, 80, 0.15); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.35); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700;">
        {$totalRows} Lignes Traitées
      </span>
    </div>

    <!-- Table Preview -->
    <div class="ai-analytics-inner" style="background: var(--ai-inner-bg, {$innerBg}); border-radius: 16px; padding: 16px; margin-bottom: 16px; overflow-x: auto; border: 1px solid rgba(255,255,255,0.06);">
      <div style="font-size: 12.5px; font-weight: 800; color: #4CAF50; text-transform: uppercase; margin-bottom: 10px;">
        <i class="v-icon notranslate mdi mdi-table" style="font-size: 16px; margin-right: 6px;"></i>
        Aperçu des Données Extraites du Fichier
      </div>
      <table class="ai-analytics-table" style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
        <thead>
          <tr style="border-bottom: 2px solid rgba(76, 175, 80, 0.2); color: var(--ai-sub-color, {$subColor}); font-size: 11.5px; text-transform: uppercase;">
            {$thHtml}
          </tr>
        </thead>
        <tbody>
          {$trHtml}
        </tbody>
      </table>
    </div>

    <div style="font-size: 13.5px; color: var(--ai-text-color, {$textColor}); line-height: 1.7; background: rgba(76, 175, 80, 0.08); padding: 14px; border-radius: 12px; border-left: 4px solid #4CAF50;">
      Les données du fichier Excel ont été intégrées et analysées. Toutes les colonnes sont disponibles pour calculs statistiques, comparaisons et rapprochements avec la base de données.
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
