<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FishProcessType;

class ProductionYieldController extends Controller
{
    /**
     * Production Yield Chart Data
     *
     * Aggregates daily weights from warehouse & non-warehouse operations,
     * grouped by fish_process_type, and calculates production + workforce yields.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // ── 1. Fish Process Types lookup ──
        $processTypes = FishProcessType::whereNull('deleted_at')->get()->keyBy('id');

        // ── 2. Warehouse operations: weight by date + fish_process_type ──
        $warehouseOps = DB::table('warehouse_stockable_tracking_number_operations as wop')
            ->join('fish_types as ft', function ($j) {
                $j->on('wop.stockable_id', '=', 'ft.id')
                  ->where('wop.stockable_type', '=', 'App\\Models\\FishType');
            })
            ->leftJoin('warehouse_category_operations as wco', 'wop.warehouse_category_operation_id', '=', 'wco.id')
            ->whereNull('wop.deleted_at')
            ->whereNull('ft.deleted_at')
            ->where('wop.type_operation', 'in')
            ->whereBetween('wop.date', [$dateFrom, $dateTo])
            ->select(
                'wop.date',
                'ft.fish_process_type_id',
                'ft.type as fish_type',
                'wco.production_role',
                DB::raw('SUM(wop.total_weight) as total_weight')
            )
            ->groupBy('wop.date', 'ft.fish_process_type_id', 'ft.type', 'wco.production_role')
            ->get();

        // ── 3. Non-warehouse operations: weight by date + fish_process_type ──
        $nonWarehouseOps = DB::table('non_warehouse_stockable_operations as nwop')
            ->join('fish_types as ft', function ($j) {
                $j->on('nwop.stockable_id', '=', 'ft.id')
                  ->where('nwop.stockable_type', '=', 'App\\Models\\FishType');
            })
            ->leftJoin('warehouse_category_operations as wco', 'nwop.warehouse_category_operation_id', '=', 'wco.id')
            ->whereNull('nwop.deleted_at')
            ->whereNull('ft.deleted_at')
            ->whereBetween('nwop.date', [$dateFrom, $dateTo])
            ->select(
                'nwop.date',
                'ft.fish_process_type_id',
                'ft.type as fish_type',
                'wco.production_role',
                DB::raw('SUM(nwop.total_weight) as total_weight')
            )
            ->groupBy('nwop.date', 'ft.fish_process_type_id', 'ft.type', 'wco.production_role')
            ->get();

        // ── 4. Fortnight activities: cost by date ──
        $fortnightCosts = DB::table('fortnight_activities')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->select('date', DB::raw('SUM(price) as total_cost'))
            ->groupBy('date')
            ->pluck('total_cost', 'date')
            ->toArray();

        // ── 5. Aggregate into daily structure ──
        $dailyData = [];

        $allOps = $warehouseOps->concat($nonWarehouseOps);

        foreach ($allOps as $row) {
            $date = $row->date;
            $processTypeId = $row->fish_process_type_id;
            $fishType = $row->fish_type;
            $productionRole = $row->production_role;
            $weight = (float) $row->total_weight;

            if (!$processTypeId) continue;

            $processName = $processTypes->has($processTypeId)
                ? $processTypes[$processTypeId]->name
                : "Unknown #{$processTypeId}";

            // Build series key: e.g., "Fresh - Entier", "Frozen - Entier"
            $typeLabel = ucfirst($fishType ?? 'other');
            $seriesKey = "{$typeLabel} - {$processName}";

            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'series' => [],
                    'fresh_weight' => 0,
                    'frozen_weight' => 0,
                ];
            }

            $dailyData[$date]['series'][$seriesKey] = ($dailyData[$date]['series'][$seriesKey] ?? 0) + $weight;

            // Accumulate by production role for yield calculation
            if ($productionRole === 'fresh') {
                $dailyData[$date]['fresh_weight'] += $weight;
            } elseif ($productionRole === 'frozen') {
                $dailyData[$date]['frozen_weight'] += $weight;
            }
        }

        // ── 6. Build sorted dates + series arrays ──
        ksort($dailyData);
        $dates = array_keys($dailyData);

        // Collect all unique series keys
        $allSeriesKeys = [];
        foreach ($dailyData as $d) {
            foreach (array_keys($d['series']) as $k) {
                $allSeriesKeys[$k] = true;
            }
        }
        $allSeriesKeys = array_keys($allSeriesKeys);
        sort($allSeriesKeys);

        // Build column series
        $seriesColumns = [];
        foreach ($allSeriesKeys as $key) {
            $seriesColumns[$key] = [];
            foreach ($dates as $date) {
                $seriesColumns[$key][] = round($dailyData[$date]['series'][$key] ?? 0, 2);
            }
        }

        // Build yield lines
        $productionYield = [];
        $workforceYield = [];

        foreach ($dates as $date) {
            $freshW = $dailyData[$date]['fresh_weight'];
            $frozenW = $dailyData[$date]['frozen_weight'];

            // Production yield: frozen out / fresh in * 100
            $productionYield[] = $freshW > 0 ? round(($frozenW / $freshW) * 100, 1) : null;

            // Workforce yield: fortnight cost / fresh weight = DH/KG
            $cost = (float) ($fortnightCosts[$date] ?? 0);
            $workforceYield[] = $freshW > 0 ? round($cost / $freshW, 2) : null;
        }

        // ── 7. Summary totals ──
        $totalFresh = array_sum(array_column(array_values($dailyData), 'fresh_weight'));
        $totalFrozen = array_sum(array_column(array_values($dailyData), 'frozen_weight'));
        $totalFortnightCost = array_sum($fortnightCosts);

        return response()->json([
            'dates'             => $dates,
            'series_columns'    => $seriesColumns,
            'production_yield'  => $productionYield,
            'workforce_yield'   => $workforceYield,
            'summary'           => [
                'total_fresh_weight'  => round($totalFresh, 2),
                'total_frozen_weight' => round($totalFrozen, 2),
                'avg_production_yield' => $totalFresh > 0 ? round(($totalFrozen / $totalFresh) * 100, 1) : 0,
                'total_workforce_cost' => round($totalFortnightCost, 2),
                'avg_workforce_yield'  => $totalFresh > 0 ? round($totalFortnightCost / $totalFresh, 2) : 0,
            ],
        ]);
    }
}
