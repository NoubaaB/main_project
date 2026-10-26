<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Charge;
use App\Models\Production;
use App\Models\Shipping;
use App\Models\ChargeCategory;
use App\Models\DetailChargeCategory;
use App\Models\ProductionCategory;
use App\Models\DetailProductionCategory;
use App\Models\ShippingCategory;
use App\Models\DetailShippingCategory;
use App\Models\CostConfiguration;
use App\Models\FishProcessType;
use App\Models\GuanoType;
use App\Models\FishType;
use Carbon\Carbon;

class JupiterDashboardController extends Controller
{
    /**
     * GET /api/jupiter-dashboard/chart-data
     *
     * Returns cumulative time-series data for factory charges, productions, shippings,
     * category breakdowns, production balance (fresh reception, frozen, guano, equilibrium),
     * process yields (% Guano / Frozen Fish and % Frozen / Fresh), and detailed statistics
     * matching the analytics & statistics capabilities of production/list.
     */
    public function chartData(Request $request)
    {
        $request->validate([
            'start'                   => 'required|date',
            'end'                     => 'required|date|after_or_equal:start',
            'periodicity'             => 'nullable|in:daily,monthly,yearly',
            'measures'                => 'nullable|array',
            'measures.*'              => 'in:weight,price',
            'filters'                 => 'nullable|json',
            'charge_category_ids'     => 'nullable|array',
            'production_category_ids' => 'nullable|array',
            'shipping_category_ids'   => 'nullable|array',
            'process_type_ids'        => 'nullable|array',
        ]);

        $start       = Carbon::parse($request->start)->startOfDay();
        $end         = Carbon::parse($request->end)->endOfDay();
        $periodicity = $request->input('periodicity', 'daily');
        $measures    = $request->input('measures', ['weight', 'price']);
        $filters     = $request->input('filters') ? json_decode($request->input('filters'), true) : [];

        // Build period grouping SQL
        $periodSelect     = $this->getPeriodSelect($periodicity, 'date');
        $periodGroup      = $this->getPeriodGroup($periodicity, 'date');
        $prodPeriodSelect = $this->getPeriodSelect($periodicity, 'productions.date');
        $prodPeriodGroup  = $this->getPeriodGroup($periodicity, 'productions.date');
        $chargPeriodSelect = $this->getPeriodSelect($periodicity, 'charges.date');
        $chargPeriodGroup  = $this->getPeriodGroup($periodicity, 'charges.date');

        // Generate all period labels for the date range
        $categories = $this->generateCategories($start, $end, $periodicity);

        // ════════════════════════════════════════════════════════════════
        // 1. CHARGES (Total, By-Category & Fresh Reception)
        // ════════════════════════════════════════════════════════════════
        $chargesBaseQuery = Charge::whereBetween('date', [$start, $end]);
        $this->applyFilters($chargesBaseQuery, $filters, 'charges', 'detailCharges');

        $chargesSummary = (clone $chargesBaseQuery)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')
            ->first();

        $chargesSummaryData = [
            'count'        => (int) ($chargesSummary->cnt ?? 0),
            'total_weight' => round((float) ($chargesSummary->tw ?? 0), 2),
            'total_price'  => round((float) ($chargesSummary->tp ?? 0), 2),
        ];

        // Charges per-period totals
        $chargesPeriodRows = (clone $chargesBaseQuery)
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw($periodGroup)
            ->get()
            ->keyBy('period');

        $chargesTotalWeight = [];
        $chargesTotalPrice  = [];
        foreach ($categories as $cat) {
            $chargesTotalWeight[] = round((float) ($chargesPeriodRows[$cat]->tw ?? 0), 2);
            $chargesTotalPrice[]  = round((float) ($chargesPeriodRows[$cat]->tp ?? 0), 2);
        }

        // Charges by Category
        $chargeCategories = ChargeCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get();
        $chargesCatRows = (clone $chargesBaseQuery)
            ->whereNotNull('charge_category_id')
            ->selectRaw("charge_category_id, {$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw("charge_category_id, {$periodGroup}")
            ->get();

        $chargesByCategory = [];
        $chargesCategoryStats = [];
        foreach ($chargeCategories as $cc) {
            $rowsForCat = $chargesCatRows->where('charge_category_id', $cc->id)->keyBy('period');
            $wData = [];
            $pData = [];
            $catTotalW = 0;
            $catTotalP = 0;
            foreach ($categories as $cat) {
                $w = (float) ($rowsForCat[$cat]->tw ?? 0);
                $p = (float) ($rowsForCat[$cat]->tp ?? 0);
                $wData[] = round($w, 2);
                $pData[] = round($p, 2);
                $catTotalW += $w;
                $catTotalP += $p;
            }
            $catCount = (clone $chargesBaseQuery)->where('charge_category_id', $cc->id)->count();
            $chargesByCategory[] = [
                'id'           => $cc->id,
                'name'         => $cc->name,
                'name_fr'      => $cc->name_fr ?: $cc->name,
                'name_en'      => $cc->name_en ?: $cc->name,
                'name_es'      => $cc->name_es ?: $cc->name,
                'weight'       => $wData,
                'price'        => $pData,
                'total_weight' => round($catTotalW, 2),
                'total_price'  => round($catTotalP, 2),
            ];
            if ($catCount > 0 || $catTotalW > 0) {
                $chargesCategoryStats[] = [
                    'id'           => $cc->id,
                    'name'         => $cc->name_fr ?: ($cc->name_en ?: $cc->name),
                    'count'        => $catCount,
                    'total_weight' => round($catTotalW, 2),
                    'total_price'  => round($catTotalP, 2),
                ];
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 2. PRODUCTIONS (Total, By-Category, Invoicing & Packaging)
        // ════════════════════════════════════════════════════════════════
        $productionsBaseQuery = Production::whereBetween('date', [$start, $end]);
        $this->applyFilters($productionsBaseQuery, $filters, 'productions', 'detailProductions');

        $prodSummary = (clone $productionsBaseQuery)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp, COALESCE(SUM(total_secondary_packaging_cost),0) as tsec')
            ->first();

        $prodSummaryData = [
            'count'        => (int) ($prodSummary->cnt ?? 0),
            'total_weight' => round((float) ($prodSummary->tw ?? 0), 2),
            'total_price'  => round((float) ($prodSummary->tp ?? 0), 2),
            'total_sec_cost' => round((float) ($prodSummary->tsec ?? 0), 2),
        ];

        $prodPeriodRows = (clone $productionsBaseQuery)
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw($periodGroup)
            ->get()
            ->keyBy('period');

        $prodTotalWeight = [];
        $prodTotalPrice  = [];
        foreach ($categories as $cat) {
            $prodTotalWeight[] = round((float) ($prodPeriodRows[$cat]->tw ?? 0), 2);
            $prodTotalPrice[]  = round((float) ($prodPeriodRows[$cat]->tp ?? 0), 2);
        }

        // Invoicing stats from productions
        $invoicedProdQuery = (clone $productionsBaseQuery)->where(function ($q) {
            $q->whereNotNull('supplier_invoice_id')->orWhereNotNull('client_invoice_id');
        });
        $invoicedCount = (clone $invoicedProdQuery)->count();
        $invoicedWeight = (clone $invoicedProdQuery)->sum('total_weight');
        $invoicedPrice  = (clone $invoicedProdQuery)->sum('total_price');

        $notInvoicedCount = max(0, $prodSummaryData['count'] - $invoicedCount);

        // Productions by Category & CategoryStats
        $productionCategories = ProductionCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get();
        $prodCatRows = (clone $productionsBaseQuery)
            ->whereNotNull('production_category_id')
            ->selectRaw("production_category_id, {$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw("production_category_id, {$periodGroup}")
            ->get();

        $productionsByCategory = [];
        $productionCategoryStats = [];
        $grandProdWeight = max(0.001, $prodSummaryData['total_weight']);

        foreach ($productionCategories as $pc) {
            $rowsForCat = $prodCatRows->where('production_category_id', $pc->id)->keyBy('period');
            $wData = [];
            $pData = [];
            $catTotalW = 0;
            $catTotalP = 0;
            foreach ($categories as $cat) {
                $w = (float) ($rowsForCat[$cat]->tw ?? 0);
                $p = (float) ($rowsForCat[$cat]->tp ?? 0);
                $wData[] = round($w, 2);
                $pData[] = round($p, 2);
                $catTotalW += $w;
                $catTotalP += $p;
            }

            $catProductions = (clone $productionsBaseQuery)->where('production_category_id', $pc->id);
            $catCount = (clone $catProductions)->count();

            // Invoiced part for this category
            $catInvoiced = (clone $catProductions)->where(function ($q) {
                $q->whereNotNull('supplier_invoice_id')->orWhereNotNull('client_invoice_id');
            });
            $catInvW = (float) $catInvoiced->sum('total_weight');
            $catInvP = (float) $catInvoiced->sum('total_price');

            $productionsByCategory[] = [
                'id'           => $pc->id,
                'name'         => $pc->name,
                'name_fr'      => $pc->name_fr ?: $pc->name,
                'name_en'      => $pc->name_en ?: $pc->name,
                'name_es'      => $pc->name_es ?: $pc->name,
                'weight'       => $wData,
                'price'        => $pData,
                'total_weight' => round($catTotalW, 2),
                'total_price'  => round($catTotalP, 2),
            ];

            if ($catCount > 0 || $catTotalW > 0) {
                $productionCategoryStats[] = [
                    'id'            => $pc->id,
                    'name'          => $pc->name_fr ?: ($pc->name_en ?: $pc->name),
                    'count'         => $catCount,
                    'weight'        => round($catTotalW, 2),
                    'price'         => round($catTotalP, 2),
                    'invoiceWeight' => round($catInvW, 2),
                    'invoicePrice'  => round($catInvP, 2),
                    'percent'       => round(($catTotalW / $grandProdWeight) * 100, 1),
                ];
            }
        }

        // Secondary Packaging Component breakdown
        $secondaryPackagingStats = $this->computeSecondaryPackagingStats($start, $end);

        // ════════════════════════════════════════════════════════════════
        // 3. SHIPPINGS (Total & By-Category)
        // ════════════════════════════════════════════════════════════════
        $shippingsBaseQuery = Shipping::whereBetween('date', [$start, $end]);
        $this->applyFilters($shippingsBaseQuery, $filters, 'shippings', 'detailShippings');

        $shipSummary = (clone $shippingsBaseQuery)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')
            ->first();

        $shipSummaryData = [
            'count'        => (int) ($shipSummary->cnt ?? 0),
            'total_weight' => round((float) ($shipSummary->tw ?? 0), 2),
            'total_price'  => round((float) ($shipSummary->tp ?? 0), 2),
        ];

        $shipPeriodRows = (clone $shippingsBaseQuery)
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw($periodGroup)
            ->get()
            ->keyBy('period');

        $shipTotalWeight = [];
        $shipTotalPrice  = [];
        foreach ($categories as $cat) {
            $shipTotalWeight[] = round((float) ($shipPeriodRows[$cat]->tw ?? 0), 2);
            $shipTotalPrice[]  = round((float) ($shipPeriodRows[$cat]->tp ?? 0), 2);
        }

        // Shippings by Category
        $shippingCategories = ShippingCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get();
        $shipCatRows = (clone $shippingsBaseQuery)
            ->whereNotNull('shipping_category_id')
            ->selectRaw("shipping_category_id, {$periodSelect} as period, COALESCE(SUM(total_weight), 0) as tw, COALESCE(SUM(total_price), 0) as tp")
            ->groupByRaw("shipping_category_id, {$periodGroup}")
            ->get();

        $shippingsByCategory = [];
        $shippingsCategoryStats = [];
        foreach ($shippingCategories as $sc) {
            $rowsForCat = $shipCatRows->where('shipping_category_id', $sc->id)->keyBy('period');
            $wData = [];
            $pData = [];
            $catTotalW = 0;
            $catTotalP = 0;
            foreach ($categories as $cat) {
                $w = (float) ($rowsForCat[$cat]->tw ?? 0);
                $p = (float) ($rowsForCat[$cat]->tp ?? 0);
                $wData[] = round($w, 2);
                $pData[] = round($p, 2);
                $catTotalW += $w;
                $catTotalP += $p;
            }
            $catCount = (clone $shippingsBaseQuery)->where('shipping_category_id', $sc->id)->count();
            $shippingsByCategory[] = [
                'id'           => $sc->id,
                'name'         => $sc->name,
                'name_fr'      => $sc->name_fr ?: $sc->name,
                'name_en'      => $sc->name_en ?: $sc->name,
                'name_es'      => $sc->name_es ?: $sc->name,
                'weight'       => $wData,
                'price'        => $pData,
                'total_weight' => round($catTotalW, 2),
                'total_price'  => round($catTotalP, 2),
            ];
            if ($catCount > 0 || $catTotalW > 0) {
                $shippingsCategoryStats[] = [
                    'id'           => $sc->id,
                    'name'         => $sc->name_fr ?: ($sc->name_en ?: $sc->name),
                    'count'        => $catCount,
                    'total_weight' => round($catTotalW, 2),
                    'total_price'  => round($catTotalP, 2),
                ];
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 4. PRODUCTION MASS BALANCE & EQUILIBRIUM (Exact like production/list)
        // ════════════════════════════════════════════════════════════════
        // Fresh reception (from charges with is_production=true & fish_types.type='fresh')
        $freshRows = DB::table('detail_charges')
            ->join('charges', 'detail_charges.charge_id', '=', 'charges.id')
            ->join('charge_categories', 'charges.charge_category_id', '=', 'charge_categories.id')
            ->join('fish_types', function ($join) {
                $join->on('detail_charges.stockable_id', '=', 'fish_types.id')
                    ->whereIn('detail_charges.stockable_type', ['FishType', 'App\\Models\\FishType']);
            })
            ->whereNull('detail_charges.deleted_at')
            ->whereNull('charges.deleted_at')
            ->whereNull('charge_categories.deleted_at')
            ->where('charge_categories.is_production', true)
            ->where('fish_types.type', 'fresh')
            ->whereBetween('charges.date', [$start, $end])
            ->selectRaw("{$chargPeriodSelect} as period, COALESCE(SUM(detail_charges.weight), 0) as total_weight")
            ->groupByRaw($chargPeriodGroup)
            ->pluck('total_weight', 'period')
            ->toArray();

        // Frozen production (from detail_productions with fish_types.type='frozen')
        $frozenRows = DB::table('detail_productions')
            ->join('productions', 'detail_productions.production_id', '=', 'productions.id')
            ->join('fish_types', function ($join) {
                $join->on('detail_productions.stockable_id', '=', 'fish_types.id')
                    ->whereIn('detail_productions.stockable_type', ['FishType', 'App\\Models\\FishType']);
            })
            ->whereNull('detail_productions.deleted_at')
            ->whereNull('productions.deleted_at')
            ->where('fish_types.type', 'frozen')
            ->whereBetween('productions.date', [$start, $end])
            ->selectRaw("{$prodPeriodSelect} as period, COALESCE(SUM(detail_productions.weight), 0) as total_weight")
            ->groupByRaw($prodPeriodGroup)
            ->pluck('total_weight', 'period')
            ->toArray();

        // Guano production (from detail_productions with GuanoType)
        $guanoRows = DB::table('detail_productions')
            ->join('productions', 'detail_productions.production_id', '=', 'productions.id')
            ->whereNull('detail_productions.deleted_at')
            ->whereNull('productions.deleted_at')
            ->whereIn('detail_productions.stockable_type', ['GuanoType', 'App\\Models\\GuanoType'])
            ->whereBetween('productions.date', [$start, $end])
            ->selectRaw("{$prodPeriodSelect} as period, COALESCE(SUM(detail_productions.weight), 0) as total_weight")
            ->groupByRaw($prodPeriodGroup)
            ->pluck('total_weight', 'period')
            ->toArray();

        $freshReceptionSeries  = [];
        $frozenProductionSeries = [];
        $guanoProductionSeries  = [];
        $equilibriumSeries     = [];

        $sumFreshReception  = 0;
        $sumFrozenProduction = 0;
        $sumGuanoProduction  = 0;

        foreach ($categories as $cat) {
            $fresh  = round((float) ($freshRows[$cat] ?? 0), 2);
            $frozen = round((float) ($frozenRows[$cat] ?? 0), 2);
            $guano  = round((float) ($guanoRows[$cat] ?? 0), 2);
            $eq     = round(($frozen + $guano) - $fresh, 2);

            $freshReceptionSeries[]  = ['x' => $cat, 'y' => $fresh];
            $frozenProductionSeries[] = ['x' => $cat, 'y' => $frozen];
            $guanoProductionSeries[]  = ['x' => $cat, 'y' => $guano];
            $equilibriumSeries[]     = [
                'x'     => $cat,
                'y'     => $eq,
                'label' => $eq >= 0 ? 'surplus' : 'shortage',
            ];

            $sumFreshReception  += $fresh;
            $sumFrozenProduction += $frozen;
            $sumGuanoProduction  += $guano;
        }

        $totalEquilibrium = round(($sumFrozenProduction + $sumGuanoProduction) - $sumFreshReception, 2);

        // ════════════════════════════════════════════════════════════════
        // 5. YIELD CALCULATION (Per Process Type)
        // ════════════════════════════════════════════════════════════════
        // Guano yields by process type
        $guanoYieldRows = DB::table('detail_productions')
            ->join('productions', 'productions.id', '=', 'detail_productions.production_id')
            ->join('guano_types', 'guano_types.id', '=', 'detail_productions.stockable_id')
            ->whereIn('detail_productions.stockable_type', ['GuanoType', 'App\\Models\\GuanoType'])
            ->whereBetween('productions.date', [$start, $end])
            ->whereNull('productions.deleted_at')
            ->whereNull('detail_productions.deleted_at')
            ->whereNotNull('guano_types.fish_process_type_id')
            ->selectRaw("guano_types.fish_process_type_id, {$prodPeriodSelect} as period, SUM(detail_productions.weight) as total_guano")
            ->groupByRaw("guano_types.fish_process_type_id, {$prodPeriodGroup}")
            ->get();

        // Frozen fish by process type
        $fishYieldRows = DB::table('detail_productions')
            ->join('productions', 'productions.id', '=', 'detail_productions.production_id')
            ->join('fish_types', 'fish_types.id', '=', 'detail_productions.stockable_id')
            ->whereIn('detail_productions.stockable_type', ['FishType', 'App\\Models\\FishType'])
            ->where('fish_types.type', 'frozen')
            ->whereBetween('productions.date', [$start, $end])
            ->whereNull('productions.deleted_at')
            ->whereNull('detail_productions.deleted_at')
            ->whereNotNull('fish_types.fish_process_type_id')
            ->selectRaw("fish_types.fish_process_type_id, {$prodPeriodSelect} as period, SUM(detail_productions.weight) as total_frozen")
            ->groupByRaw("fish_types.fish_process_type_id, {$prodPeriodGroup}")
            ->get();

        // Fresh fish charges by process type (for production process yield calculation)
        $freshYieldRows = DB::table('detail_charges')
            ->join('charges', 'charges.id', '=', 'detail_charges.charge_id')
            ->join('charge_categories', 'charges.charge_category_id', '=', 'charge_categories.id')
            ->join('fish_types', 'fish_types.id', '=', 'detail_charges.stockable_id')
            ->whereIn('detail_charges.stockable_type', ['FishType', 'App\\Models\\FishType'])
            ->where('charge_categories.is_production', true)
            ->where('fish_types.type', 'fresh')
            ->whereBetween('charges.date', [$start, $end])
            ->whereNull('charges.deleted_at')
            ->whereNull('detail_charges.deleted_at')
            ->whereNotNull('fish_types.fish_process_type_id')
            ->selectRaw("fish_types.fish_process_type_id, {$chargPeriodSelect} as period, SUM(detail_charges.weight) as total_fresh")
            ->groupByRaw("fish_types.fish_process_type_id, {$chargPeriodGroup}")
            ->get();

        $processTypes = FishProcessType::all();
        $yields = [];
        $yieldSummary = [];

        foreach ($processTypes as $pt) {
            $gMap = $guanoYieldRows->where('fish_process_type_id', $pt->id)->pluck('total_guano', 'period')->toArray();
            $fMap = $fishYieldRows->where('fish_process_type_id', $pt->id)->pluck('total_frozen', 'period')->toArray();
            $cMap = $freshYieldRows->where('fish_process_type_id', $pt->id)->pluck('total_fresh', 'period')->toArray();

            $guanoData  = [];
            $frozenData = [];
            $freshData  = [];
            $yieldData  = []; // Guano / Frozen * 100
            $prodYieldData = []; // Frozen / Fresh * 100

            $sumGuano  = 0;
            $sumFrozen = 0;
            $sumFresh  = 0;

            foreach ($categories as $cat) {
                $g = (float) ($gMap[$cat] ?? 0);
                $f = (float) ($fMap[$cat] ?? 0);
                $c = (float) ($cMap[$cat] ?? 0);

                $y = $f > 0 ? round(($g / $f) * 100, 2) : 0;
                $py = $c > 0 ? round(($f / $c) * 100, 2) : 0;

                $guanoData[]     = round($g, 2);
                $frozenData[]    = round($f, 2);
                $freshData[]     = round($c, 2);
                $yieldData[]     = $y;
                $prodYieldData[] = $py;

                $sumGuano  += $g;
                $sumFrozen += $f;
                $sumFresh  += $c;
            }

            $overallYield = $sumFrozen > 0 ? round(($sumGuano / $sumFrozen) * 100, 2) : 0;
            $overallProdYield = $sumFresh > 0 ? round(($sumFrozen / $sumFresh) * 100, 2) : 0;

            $yieldRecord = [
                'id'                 => $pt->id,
                'name'               => $pt->name,
                'productivity_color' => $pt->productivity_color,
                'guano_weight'       => $guanoData,
                'frozen_weight'      => $frozenData,
                'fresh_weight'       => $freshData,
                'yield_percent'      => $yieldData,
                'prod_yield_percent' => $prodYieldData,
                'total_guano'        => round($sumGuano, 2),
                'total_frozen'       => round($sumFrozen, 2),
                'total_fresh'        => round($sumFresh, 2),
                'overall_yield'      => $overallYield,
                'overall_prod_yield' => $overallProdYield,
            ];

            $yields[] = $yieldRecord;
            $yieldSummary[] = [
                'id'                 => $pt->id,
                'name'               => $pt->name,
                'total_guano'        => round($sumGuano, 2),
                'total_frozen'       => round($sumFrozen, 2),
                'total_fresh'        => round($sumFresh, 2),
                'overall_yield'      => $overallYield,
                'overall_prod_yield' => $overallProdYield,
            ];
        }

        // ════════════════════════════════════════════════════════════════
        // 6. ASSEMBLE COMPLETE RESPONSE
        // ════════════════════════════════════════════════════════════════
        $costLines = $this->buildCostLines($start, $end, $periodSelect, $periodGroup, $categories, $filters);

        return response()->json([
            'categories'  => $categories,
            'periodicity' => $periodicity,

            // Raw balance series exactly matching production/list
            'analytics' => [
                'fresh_reception'   => $freshReceptionSeries,
                'frozen_production' => $frozenProductionSeries,
                'guano_production'  => $guanoProductionSeries,
                'equilibrium'       => $equilibriumSeries,
            ],

            // Operations totals & by-category breakdowns
            'operations'  => [
                'charges' => [
                    'total_weight' => $chargesTotalWeight,
                    'total_price'  => $chargesTotalPrice,
                    'by_category'  => $chargesByCategory,
                ],
                'productions' => [
                    'total_weight' => $prodTotalWeight,
                    'total_price'  => $prodTotalPrice,
                    'by_category'  => $productionsByCategory,
                ],
                'shippings' => [
                    'total_weight' => $shipTotalWeight,
                    'total_price'  => $shipTotalPrice,
                    'by_category'  => $shippingsByCategory,
                ],
            ],

            // Yield metrics
            'yields'      => $yields,

            // Consolidated statistics exact like production/list
            'statistics'  => [
                'totals' => [
                    'fresh_reception'   => round($sumFreshReception, 2),
                    'frozen_production' => round($sumFrozenProduction, 2),
                    'guano_production'  => round($sumGuanoProduction, 2),
                    'equilibrium'       => $totalEquilibrium,
                    'is_surplus'        => $totalEquilibrium >= 0,
                    'charges'           => $chargesSummaryData,
                    'productions'       => $prodSummaryData,
                    'shippings'         => $shipSummaryData,
                ],
                'invoicing' => [
                    'total_invoiced'     => $invoicedCount,
                    'total_not_invoiced' => $notInvoicedCount,
                    'invoiced_weight'    => round($invoicedWeight, 2),
                    'invoiced_price'     => round($invoicedPrice, 2),
                ],
                'category_stats'          => $productionCategoryStats,
                'charges_category_stats'  => $chargesCategoryStats,
                'shipping_category_stats' => $shippingsCategoryStats,
                'secondary_packaging'     => $secondaryPackagingStats,
                'yields'                  => $yieldSummary,
            ],

            // Category options for filters
            'category_options' => [
                'charge_categories'     => $chargeCategories,
                'production_categories' => $productionCategories,
                'shipping_categories'   => $shippingCategories,
                'fish_process_types'    => $processTypes,
            ],

            'cost_lines' => $costLines,
        ]);
    }

    /**
     * Compute secondary packaging statistics from productions in the date range.
     */
    private function computeSecondaryPackagingStats(Carbon $start, Carbon $end): array
    {
        $defaultComps = [
            'caisses'    => ['key' => 'caisses',    'label' => 'Caisses',    'unit' => 'U',       'qte' => 0.0, 'total_cost' => 0.0],
            'cellophane' => ['key' => 'cellophane', 'label' => 'Cellophane', 'unit' => 'Kg',      'qte' => 0.0, 'total_cost' => 0.0],
            'plastique'  => ['key' => 'plastique',  'label' => 'Plastique',  'unit' => 'Kg',      'qte' => 0.0, 'total_cost' => 0.0],
            'strapex'    => ['key' => 'strapex',    'label' => 'Strapex',    'unit' => 'm',       'qte' => 0.0, 'total_cost' => 0.0],
            'palletes'   => ['key' => 'palletes',   'label' => 'Palettes',   'unit' => 'U',       'qte' => 0.0, 'total_cost' => 0.0],
            'scotch'     => ['key' => 'scotch',     'label' => 'Scotch',     'unit' => 'Rouleau', 'qte' => 0.0, 'total_cost' => 0.0],
        ];

        $details = DB::table('detail_productions')
            ->join('productions', 'productions.id', '=', 'detail_productions.production_id')
            ->whereBetween('productions.date', [$start, $end])
            ->whereNull('productions.deleted_at')
            ->whereNull('detail_productions.deleted_at')
            ->whereNotNull('detail_productions.secondary_packaging_details')
            ->select('detail_productions.nbr_box', 'detail_productions.secondary_packaging_cost', 'detail_productions.secondary_packaging_details')
            ->get();

        $directSum = 0;
        foreach ($details as $row) {
            $directSum += (float) ($row->secondary_packaging_cost ?? 0);
            $sec = $row->secondary_packaging_details;
            if (is_string($sec)) {
                $sec = json_decode($sec, true);
            }
            if (!is_array($sec)) {
                continue;
            }

            $nbrBox = (float) ($sec['nbr_box'] ?? $row->nbr_box ?? 0);
            if (!empty($sec['components']) && is_array($sec['components'])) {
                foreach ($sec['components'] as $c) {
                    $k = $c['key'] ?? ($c['name'] ?? null);
                    if (!$k) continue;
                    if (!isset($defaultComps[$k])) {
                        $defaultComps[$k] = [
                            'key'        => $k,
                            'label'      => $c['name'] ?? ucfirst($k),
                            'unit'       => $c['unit'] ?? '',
                            'qte'        => 0.0,
                            'total_cost' => 0.0,
                        ];
                    }
                    $qte = (float) ($c['qte'] ?? 0);
                    $price = (float) ($c['price'] ?? 0);
                    $compQte = $nbrBox * $qte;
                    $compCost = $compQte * $price;
                    $defaultComps[$k]['qte'] += $compQte;
                    $defaultComps[$k]['total_cost'] += $compCost;
                }
            } else {
                foreach (['caisses', 'cellophane', 'plastique', 'strapex', 'palletes', 'scotch'] as $comp) {
                    $qte = (float) ($sec["{$comp}_qte"] ?? 0);
                    $price = (float) ($sec["{$comp}_price"] ?? 0);
                    $compQte = $nbrBox * $qte;
                    $compCost = $compQte * $price;
                    $defaultComps[$comp]['qte'] += $compQte;
                    $defaultComps[$comp]['total_cost'] += $compCost;
                }
            }
        }

        $items = [];
        $calculatedTotal = 0;
        foreach ($defaultComps as $item) {
            $item['qte'] = round($item['qte'], 3);
            $item['total_cost'] = round($item['total_cost'], 2);
            $calculatedTotal += $item['total_cost'];
            $items[] = $item;
        }

        $grandTotal = max(round($calculatedTotal, 2), round($directSum, 2));

        return [
            'total_cost' => $grandTotal,
            'items'      => $items,
        ];
    }

    // ────────────────────────────────────────────────────────────────
    //  Private helpers
    // ────────────────────────────────────────────────────────────────

    private function applyFilters($query, array $filters, string $tableName, string $detailRelation)
    {
        foreach ($filters as $group) {
            if (($group['table'] ?? '') !== $tableName) {
                continue;
            }

            if (!empty($group['rules'])) {
                foreach ($group['rules'] as $rule) {
                    $this->applyRule($query, $rule);
                }
            }

            if (!empty($group['detail_rules'])) {
                $detailRules = $group['detail_rules'];
                $query->whereHas($detailRelation, function ($q) use ($detailRules) {
                    foreach ($detailRules as $rule) {
                        $this->applyRule($q, $rule);
                    }
                });
            }
        }
    }

    private function applyRule($query, array $rule)
    {
        $column   = $rule['column']   ?? null;
        $operator = $rule['operator'] ?? '=';
        $value    = $rule['value']    ?? null;

        if (!$column) return;

        $allowed = [
            'total_price', 'total_weight', 'charge_category_id', 'supplier_id',
            'fishery_id', 'ship_id', 'origin_id', 'detail_charge_category_id',
            'weight', 'unit_price', 'stockable_type', 'stockable_id',
            'warehouse_id', 'nbr_box', 'production_category_id', 'production_type',
            'client_id', 'shipping_category_id', 'direction',
            'detail_production_category_id', 'detail_shipping_category_id',
        ];
        if (!in_array($column, $allowed)) return;

        $allowedOps = ['=', '!=', '>', '<', '>=', '<=', 'between', 'like'];
        if (!in_array($operator, $allowedOps)) return;

        if ($operator === 'between') {
            if (is_array($value) && count($value) === 2) {
                $query->whereBetween($column, [$value[0], $value[1]]);
            }
        } elseif ($operator === 'like') {
            $query->where($column, 'LIKE', '%' . $value . '%');
        } else {
            $query->where($column, $operator, $value);
        }
    }

    private function getPeriodSelect(string $periodicity, string $column = 'date'): string
    {
        return match ($periodicity) {
            'monthly' => "DATE_FORMAT($column, '%Y-%m')",
            'yearly'  => "YEAR($column)",
            default   => "DATE($column)",
        };
    }

    private function getPeriodGroup(string $periodicity, string $column = 'date'): string
    {
        return match ($periodicity) {
            'monthly' => "DATE_FORMAT($column, '%Y-%m')",
            'yearly'  => "YEAR($column)",
            default   => "DATE($column)",
        };
    }

    private function generateCategories(Carbon $start, Carbon $end, string $periodicity): array
    {
        $categories = [];
        $cursor     = $start->copy();

        switch ($periodicity) {
            case 'monthly':
                $cursor = $cursor->startOfMonth();
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m');
                    $cursor->addMonth();
                }
                break;

            case 'yearly':
                while ($cursor->year <= $end->year) {
                    $categories[] = (string) $cursor->year;
                    $cursor->addYear();
                }
                break;

            default: // daily
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }
                break;
        }

        return $categories;
    }

    private function buildCostLines(Carbon $start, Carbon $end, string $periodSelect, string $periodGroup, array $categories, array $filters): array
    {
        $configs = CostConfiguration::where('enabled', true)->where('show_in_chart', true)->get();
        if ($configs->isEmpty()) {
            return [];
        }

        $costLines = [];
        foreach ($configs as $config) {
            $vars = $config->variables ?? [];
            if (empty($vars)) continue;

            $varValuesPerPeriod = [];
            foreach ($vars as $var) {
                $varName = $var['name'] ?? null;
                if (!$varName) continue;
                $varValuesPerPeriod[$varName] = $this->getVariablePerPeriodData($var, $start, $end, $periodSelect, $periodGroup, $categories, $filters);
            }

            $lineData = [];
            foreach ($categories as $idx => $cat) {
                $periodValues = [];
                foreach ($varValuesPerPeriod as $varName => $periodData) {
                    $periodValues[$varName] = $periodData[$idx] ?? 0;
                }
                $costVal = $this->evaluateCostFormula($config->formula, $periodValues);
                $lineData[] = round($costVal, 2);
            }

            $name = $config->name_fr ?: ($config->name_en ?: ($config->name_es ?: ('Cost #' . $config->id)));

            $costLines[] = [
                'id'         => $config->id,
                'name'       => $name,
                'color'      => $config->chart_color ?: '#F59E0B',
                'line_style' => 'solid',
                'data'       => $lineData,
            ];
        }

        return $costLines;
    }

    private function getVariablePerPeriodData(array $var, Carbon $start, Carbon $end, string $periodSelect, string $periodGroup, array $categories, array $filters): array
    {
        $dataSource = $var['data_source'] ?? 'charge';
        $valueType  = $var['value_type'] ?? 'price';
        $categoryId = $var['category_id'] ?? null;
        $sumColumn  = $valueType === 'weight' ? 'total_weight' : 'total_price';

        switch ($dataSource) {
            case 'charge':
                $query = Charge::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'charges', 'detailCharges');
                if ($categoryId) $query->where('charge_category_id', $categoryId);
                break;

            case 'production':
                $query = Production::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'productions', 'detailProductions');
                if ($categoryId) $query->where('production_category_id', $categoryId);
                break;

            case 'shipping':
                $query = Shipping::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'shippings', 'detailShippings');
                if ($categoryId) $query->where('shipping_category_id', $categoryId);
                break;

            default:
                return array_fill(0, count($categories), 0);
        }

        $rows = $query
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM({$sumColumn}), 0) as total")
            ->groupByRaw($periodGroup)
            ->pluck('total', 'period')
            ->toArray();

        $result = [];
        foreach ($categories as $cat) {
            $result[] = round((float) ($rows[$cat] ?? 0), 2);
        }

        return $result;
    }

    private function evaluateCostFormula(string $formula, array $values): float
    {
        try {
            $expr = trim($formula);
            uksort($values, fn($a, $b) => strlen($b) <=> strlen($a));

            foreach ($values as $varName => $val) {
                $numVal = is_numeric($val) ? (float) $val : 0;
                $pattern = '/\b' . preg_quote($varName, '/') . '\b/';
                $expr = preg_replace($pattern, (string) $numVal, $expr);
            }

            $expr = preg_replace('/(\d+(\.\d+)?)%/', '($1/100)', $expr);
            if (preg_match('/[^0-9\+\-\*\/\%\(\)\.\s]/', $expr)) return 0;
            $result = $this->evalMathExpression($expr);
            return ($result !== null && !is_nan($result) && !is_infinite($result)) ? round($result, 4) : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function evalMathExpression(string $expr): ?float
    {
        $expr = str_replace(' ', '', $expr);
        if ($expr === '') return 0.0;
        $expr = preg_replace('/(\d+(\.\d+)?)%/', '($1/100)', $expr);

        while (preg_match('/\(([^()]+)\)/', $expr, $matches)) {
            $subVal = $this->evalSimpleMath($matches[1]);
            if ($subVal === null) return null;
            $expr = str_replace($matches[0], (string) $subVal, $expr);
        }

        return $this->evalSimpleMath($expr);
    }

    private function evalSimpleMath(string $expr): ?float
    {
        preg_match_all('/(?:\d+\.?\d*|\.\d+)|[\+\-\*\/\%]/', $expr, $tokens);
        $tokens = $tokens[0] ?? [];
        if (empty($tokens)) return null;

        $numTokens = [];
        $i = 0;
        $cnt = count($tokens);

        while ($i < $cnt) {
            $t = $tokens[$i];
            if (($t === '-' || $t === '+') && ($i === 0 || in_array($tokens[$i - 1], ['+', '-', '*', '/', '%']))) {
                $next = $tokens[$i + 1] ?? '0';
                $numTokens[] = (float) ($t . $next);
                $i += 2;
            } else if (is_numeric($t)) {
                $numTokens[] = (float) $t;
                $i++;
            } else {
                $numTokens[] = $t;
                $i++;
            }
        }

        $pass1 = [];
        $j = 0;
        $numCnt = count($numTokens);

        while ($j < $numCnt) {
            $token = $numTokens[$j];
            if ($token === '*' || $token === '/' || $token === '%') {
                $prev = array_pop($pass1);
                $next = $numTokens[$j + 1] ?? 0;
                if ($token === '*') {
                    $pass1[] = $prev * $next;
                } else if ($token === '/') {
                    if ((float) $next === 0.0) return null;
                    $pass1[] = $prev / $next;
                } else if ($token === '%') {
                    if ((float) $next === 0.0) return null;
                    $pass1[] = fmod($prev, $next);
                }
                $j += 2;
            } else {
                $pass1[] = $token;
                $j++;
            }
        }

        if (empty($pass1)) return 0.0;
        $res = is_numeric($pass1[0]) ? (float) $pass1[0] : 0.0;
        $k = 1;
        $p1Cnt = count($pass1);

        while ($k < $p1Cnt) {
            $op = $pass1[$k];
            $val = $pass1[$k + 1] ?? 0;
            if ($op === '+') {
                $res += (float) $val;
            } else if ($op === '-') {
                $res -= (float) $val;
            }
            $k += 2;
        }

        return $res;
    }
}
