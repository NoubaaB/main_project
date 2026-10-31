<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Models\DetailCharge;
use App\Models\DetailProduction;
use App\Models\DetailInventory;
use App\Models\DetailWarehouseTransfer;
use App\Models\Charge;
use App\Models\Production;
use App\Models\FishTypeWap;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    use BroadcastsModelChanges;

    public static function groupConcat(string $column, string $separator = ', '): string
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        if ($isSqlite) {
            return "GROUP_CONCAT(DISTINCT {$column})";
        }
        return "GROUP_CONCAT(DISTINCT {$column} SEPARATOR '{$separator}')";
    }

    public static function dateDiffDays(string $dateCol): string
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        if ($isSqlite) {
            return "ROUND(julianday('now') - julianday({$dateCol}))";
        }
        return "DATEDIFF(NOW(), {$dateCol})";
    }

    public function index()
    {
        $warehouses = Warehouse::with('category')->get();
        return response()->json(['warehouses' => $warehouses]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name',
            'description' => 'nullable|string',
            'warehouse_category_id' => 'nullable|exists:warehouse_categories,id',
            'warehouse_type' => 'nullable|string|in:FishType,GuanoType,PackageType',
            'has_sejour' => 'boolean',
            'operation_service_fee' => 'nullable|numeric|min:0',
            'is_allow_price' => 'nullable|boolean',
            'allow_production_effect' => 'nullable|boolean',
        ]);
        $warehouse = Warehouse::create($data);
        $this->broadcastChange('Warehouse', 'created', $warehouse->id, $warehouse->toArray());
        return response()->json(['warehouse' => $warehouse], 201);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $warehouse->id,
            'description' => 'nullable|string',
            'warehouse_category_id' => 'nullable|exists:warehouse_categories,id',
            'warehouse_type' => 'nullable|string|in:FishType,GuanoType,PackageType',
            'has_sejour' => 'boolean',
            'operation_service_fee' => 'nullable|numeric|min:0',
            'is_allow_price' => 'nullable|boolean',
            'allow_production_effect' => 'nullable|boolean',
        ]);
        $warehouse->update($data);
        $this->broadcastChange('Warehouse', 'updated', $warehouse->id, $warehouse->toArray());
        return response()->json(['warehouse' => $warehouse]);
    }

    public function destroy(Warehouse $warehouse)
    {
        $id = $warehouse->id;
        $warehouse->delete();
        $this->broadcastChange('Warehouse', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Dashboard data: returns warehouse balances grouped for multi-level drill-down.
     * Accepts warehouse_ids[] filter.
     */
    public function dashboardData(Request $request)
    {
        $warehouseIds = $request->input('warehouse_ids', []);

        if (empty($warehouseIds)) {
            return response()->json(['balances' => [], 'warehouses' => []]);
        }

        $warehouses = Warehouse::with('category')
            ->whereIn('id', $warehouseIds)
            ->get();

        $balances = WarehouseBalance::with([
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'packageType',
                'operations',
            ])
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('warehouse_operation', 'stock_in')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'warehouses' => $warehouses,
            'balances' => $balances,
        ]);
    }

    /**
     * Get available warehouse balances for a given warehouse.
     * Returns all non-finished stock_in balances with stockable relationship loaded.
     * Optionally filtered by stockable_type and stockable_id.
     */
    public function availableBalances(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'stockable_type' => 'nullable|string',
            'stockable_id' => 'nullable|integer',
        ]);

        $query = WarehouseBalance::with(['stockable', 'operations'])
            ->where('warehouse_id', $request->warehouse_id)
            ->where('warehouse_operation', 'stock_in')
            ->where('is_finished', false);

        if ($request->stockable_type) {
            $query->where('stockable_type', $request->stockable_type);
        }
        if ($request->stockable_id) {
            $query->where('stockable_id', $request->stockable_id);
        }

        $balances = $query->orderBy('date', 'asc')->get();

        return response()->json(['balances' => $balances]);
    }

    /**
     * Returns warehouses that have total_weight > 0.
     * If max_date is specified, recalculates from balances filtered by date.
     */
    public function warehousesWithStock(Request $request)
    {
        $maxDate = $request->input('max_date');

        if ($maxDate) {
            // Date-filtered: calculate dynamically from balances using a single aggregated query
            $stockByWarehouse = WarehouseBalance::where('warehouse_operation', 'stock_in')
                ->where('is_finished', false)
                ->where('remaining_weight', '>', 0)
                ->where('date', '<=', $maxDate)
                ->groupBy('warehouse_id')
                ->selectRaw('warehouse_id, ROUND(SUM(remaining_weight), 2) as total_stock')
                ->pluck('total_stock', 'warehouse_id');

            $warehouses = Warehouse::whereIn('id', $stockByWarehouse->keys())
                ->get()
                ->map(function ($w) use ($stockByWarehouse) {
                    $w->total_stock = (float) ($stockByWarehouse[$w->id] ?? 0);
                    return $w;
                });
        } else {
            // Use cached total_weight column
            $warehouses = Warehouse::where('total_weight', '>', 0)
                ->get()
                ->map(function ($w) {
                    $w->total_stock = (float) $w->total_weight;
                    return $w;
                });
        }

        return response()->json(['warehouses' => $warehouses]);
    }

    /**
     * Returns articles (stockable items) for a given warehouse that have remaining stock.
     * Optionally filtered by max date.
     */
    public function warehouseArticles(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $maxDate = $request->input('max_date');

        $balances = WarehouseBalance::with(['stockable'])
            ->where('warehouse_id', $request->warehouse_id)
            ->where('warehouse_operation', 'stock_in')
            ->where('is_finished', false)
            ->where('remaining_weight', '>', 0)
            ->when($maxDate, fn($q) => $q->where('date', '<=', $maxDate))
            ->get();

        // Group by stockable_type + stockable_id to get unique articles
        $articles = $balances->groupBy(fn($b) => $b->stockable_type . '_' . $b->stockable_id)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'stockable_type' => $first->stockable_type,
                    'stockable_id' => $first->stockable_id,
                    'stockable' => $first->stockable,
                    'total_remaining' => round($group->sum('remaining_weight'), 2),
                    'balance_count' => $group->count(),
                ];
            })
            ->values();

        return response()->json(['articles' => $articles]);
    }

    /**
     * Returns warehouse balances for a given warehouse + article, filtered by date.
     */
    public function warehouseBalancesForTransfer(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'stockable_type' => 'required|string',
            'stockable_id' => 'required|integer',
        ]);

        $maxDate = $request->input('max_date');

        $balances = WarehouseBalance::with(['stockable', 'packageType'])
            ->where('warehouse_id', $request->warehouse_id)
            ->where('stockable_type', $request->stockable_type)
            ->where('stockable_id', $request->stockable_id)
            ->where('warehouse_operation', 'stock_in')
            ->where('is_finished', false)
            ->where('remaining_weight', '>', 0)
            ->when($maxDate, fn($q) => $q->where('date', '<=', $maxDate))
            ->orderBy('date', 'asc')
            ->get();

        return response()->json(['balances' => $balances]);
    }

    /**
     * Clean helper to extract clean lot number from raw reception_tracking_number.
     */
    public static function cleanLotNumber(?string $raw): string
    {
        if (empty($raw)) {
            return '-';
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $filtered = array_filter(array_map('trim', $decoded));
            return !empty($filtered) ? implode(', ', $filtered) : '-';
        }
        return trim(str_replace(['[', ']', '"', "'", '\\'], '', (string) $raw)) ?: '-';
    }

    /**
     * Stock Par Lot: Returns lots (reception_tracking_number) with aggregated stock,
     * status, valuation, age, and individual article details.
     */
    public function stockParLot(Request $request)
    {
        $warehouseIds = $request->input('warehouse_ids', []);
        if (is_string($warehouseIds)) {
            $warehouseIds = array_filter(explode(',', $warehouseIds));
        }

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'in_stock'); // 'all', 'in_stock', 'partially_out', 'depleted'
        $stockableTypes = $request->input('stockable_types', $request->input('stockable_type', []));
        if (is_string($stockableTypes)) {
            $stockableTypes = array_filter(explode(',', $stockableTypes));
        } elseif (!is_array($stockableTypes)) {
            $stockableTypes = [];
        }
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(5, (int) $request->input('per_page', 20)));
        $sortBy = $request->input('sort_by', 'date');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // 1. Base query for active lots aggregation
        $query = DB::table('warehouse_balances')
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->whereNotNull('reception_tracking_number')
            ->where('reception_tracking_number', '!=', '')
            ->where('reception_tracking_number', '!=', '[]')
            ->where('reception_tracking_number', '!=', '[""]');

        if (!empty($warehouseIds)) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }
        if (!empty($stockableTypes)) {
            $query->where(function ($q) use ($stockableTypes) {
                foreach ($stockableTypes as $st) {
                    $q->orWhere('stockable_type', 'like', "%{$st}%");
                }
            });
        }
        if (!empty($dateFrom)) {
            $query->where('date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->where('date', '<=', $dateTo);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reception_tracking_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('fishery_name', 'like', "%{$search}%")
                  ->orWhere('origin_name', 'like', "%{$search}%");
            });
        }

        // Fast count query without expensive GROUP_CONCATs
        $countQuery = (clone $query)->selectRaw('
            reception_tracking_number,
            ROUND(SUM(total_weight), 2) as initial_weight,
            ROUND(SUM(remaining_weight), 2) as remaining_weight,
            ROUND(SUM(package_count), 2) as initial_boxes,
            SUM(CASE WHEN remaining_weight > 0 THEN 1 ELSE 0 END) as active_sub_count
        ')->groupBy('reception_tracking_number');

        if ($status === 'in_stock') {
            $countQuery->havingRaw('remaining_weight > 0 OR (initial_boxes > 0 AND active_sub_count > 0)');
        } elseif ($status === 'depleted') {
            $countQuery->havingRaw('remaining_weight <= 0 AND active_sub_count = 0');
        } elseif ($status === 'partially_out') {
            $countQuery->havingRaw('remaining_weight > 0 AND remaining_weight < initial_weight');
        }

        $totalLots = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();

        $gcSuppliers = self::groupConcat('supplier_name', ', ');
        $gcFisheries = self::groupConcat('fishery_name', ', ');
        $gcOrigins = self::groupConcat('origin_name', ', ');
        $gcWhIds = self::groupConcat('warehouse_id', ',');
        $gcSourceTypes = self::groupConcat('source_type', ',');

        $query->selectRaw("
            reception_tracking_number,
            COUNT(*) as articles_count,
            MIN(date) as first_date,
            MAX(date) as last_date,
            ROUND(SUM(total_weight), 2) as initial_weight,
            ROUND(SUM(remaining_weight), 2) as remaining_weight,
            ROUND(SUM(package_count), 2) as initial_boxes,
            ROUND(SUM(CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END), 2) as estimated_valuation,
            {$gcSuppliers} as suppliers,
            {$gcFisheries} as fisheries,
            {$gcOrigins} as origins,
            {$gcWhIds} as wh_ids_str,
            {$gcSourceTypes} as source_types_str,
            SUM(CASE WHEN remaining_weight > 0 THEN 1 ELSE 0 END) as active_sub_count
        ")->groupBy('reception_tracking_number');

        if ($status === 'in_stock') {
            $query->havingRaw('remaining_weight > 0 OR (initial_boxes > 0 AND active_sub_count > 0)');
        } elseif ($status === 'depleted') {
            $query->havingRaw('remaining_weight <= 0 AND active_sub_count = 0');
        } elseif ($status === 'partially_out') {
            $query->havingRaw('remaining_weight > 0 AND remaining_weight < initial_weight');
        }

        $orderColumn = match ($sortBy) {
            'lot' => 'reception_tracking_number',
            'status' => 'remaining_weight',
            'init_weight' => 'initial_weight',
            'rem_weight' => 'remaining_weight',
            'boxes' => 'initial_boxes',
            'valuation' => 'estimated_valuation',
            'supplier' => 'suppliers',
            'age' => 'first_date',
            default => 'first_date',
        };

        if ($sortBy === 'status') {
            $query->orderByRaw("(CASE WHEN remaining_weight <= 0 THEN 3 WHEN remaining_weight < initial_weight THEN 2 ELSE 1 END) {$sortDir}");
        } elseif ($sortBy === 'age') {
            $query->orderBy('first_date', $sortDir === 'asc' ? 'desc' : 'asc');
        } else {
            $query->orderBy($orderColumn, $sortDir);
        }

        $paginatedLots = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $rawLotKeys = $paginatedLots->pluck('reception_tracking_number')->toArray();

        // 2. Hydrate detailed balance rows for these paginated lots
        $lotBalances = [];
        if (!empty($rawLotKeys)) {
            $balancesQuery = WarehouseBalance::with([
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'warehouse',
                'packageType',
                'operations' => fn($q) => $q->orderBy('date', 'desc'),
            ])
            ->whereIn('reception_tracking_number', $rawLotKeys)
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at');

            if (!empty($warehouseIds)) {
                $balancesQuery->whereIn('warehouse_id', $warehouseIds);
            }

            $lotBalances = $balancesQuery->orderBy('date', 'desc')->get()->groupBy('reception_tracking_number');
        }

        // 3. Transform lots into rich presentation structures
        $warehousesLookup = Warehouse::pluck('name', 'id');
        $transformedLots = $paginatedLots->map(function ($row) use ($lotBalances, $warehousesLookup) {
            $raw = $row->reception_tracking_number;
            $cleanLot = self::cleanLotNumber($raw);
            $balances = $lotBalances[$raw] ?? collect([]);

            $whIds = !empty($row->wh_ids_str) ? explode(',', $row->wh_ids_str) : [];
            $warehouses = collect($whIds)->map(fn($id) => [
                'id' => (int) $id,
                'name' => $warehousesLookup[$id] ?? ('Chambre #' . $id),
            ])->values();

            $initialW = (float) $row->initial_weight;
            $remainingW = (float) $row->remaining_weight;
            $exitedW = max(0, round($initialW - $remainingW, 2));
            $pctConsumed = $initialW > 0 ? round(($exitedW / $initialW) * 100, 1) : 0;

            $firstDate = $row->first_date ? Carbon::parse($row->first_date) : null;
            $ageDays = $firstDate ? max(0, (int) round($firstDate->diffInDays(now()))) : 0;

            $totalValuation = 0.0;
            $remainingBoxes = 0.0;
            $exitedBoxes = 0.0;
            $speciesNames = [];
            $articles = [];

            foreach ($balances as $b) {
                $artName = $b->article_name;
                $speciesNames[] = $artName;

                $wap = (float) $b->wap;
                $remW = (float) $b->remaining_weight;
                $effPrice = (float) $b->effective_total_price;
                if ($effPrice <= 0 && $b->unit_price && $remW > 0) {
                    $effPrice = round((float) $b->unit_price * $remW, 2);
                }
                $totalValuation += $effPrice;

                $bInitPkgs = (float) $b->package_count;
                $bOutPkgs = (float) $b->operations->sum('package_count');
                $bRemPkgs = max(0, $bInitPkgs - $bOutPkgs);
                $remainingBoxes += $bRemPkgs;
                $exitedBoxes += $bOutPkgs;

                $articles[] = [
                    'id' => $b->id,
                    'warehouse_id' => $b->warehouse_id,
                    'warehouse_name' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                    'article_name' => $artName,
                    'clean_type' => $b->clean_stockable_type,
                    'clean_source' => $b->clean_source_type,
                    'fish_family' => $b->stockable->fish_family->name ?? null,
                    'process' => $b->stockable->process ?? $b->stockable->fish_process_type->name ?? null,
                    'date' => $b->date ? $b->date->format('Y-m-d') : null,
                    'initial_weight' => (float) $b->total_weight,
                    'remaining_weight' => $remW,
                    'exited_weight' => max(0, round((float) $b->total_weight - $remW, 2)),
                    'initial_boxes' => $bInitPkgs,
                    'remaining_boxes' => $bRemPkgs,
                    'package_type_name' => $b->packageType->name ?? null,
                    'unit_price' => (float) $b->unit_price,
                    'wap' => $wap,
                    'total_price' => $effPrice,
                    'is_depleted' => $remW <= 0 && $bRemPkgs <= 0,
                    'operations_count' => $b->operations->count(),
                    'operations' => $b->operations->map(fn($op) => [
                        'id' => $op->id,
                        'date' => $op->date ? $op->date->format('Y-m-d') : null,
                        'source_type' => class_basename($op->source_type),
                        'source_id' => $op->source_id,
                        'weight' => (float) $op->weight,
                        'package_count' => (float) $op->package_count,
                        'client_name' => $op->client_name,
                        'unit_price' => (float) $op->unit_price,
                        'total_price' => (float) $op->total_price,
                    ])->values(),
                ];
            }

            $speciesUnique = array_values(array_unique(array_filter($speciesNames)));

            $lotStatus = 'in_stock';
            if ($remainingW <= 0 && $remainingBoxes <= 0) {
                $lotStatus = 'depleted';
            } elseif ($remainingW < $initialW || $exitedBoxes > 0) {
                $lotStatus = 'partially_out';
            }

            return [
                'lot_number' => $cleanLot,
                'raw_lot' => $raw,
                'first_date' => $row->first_date,
                'last_date' => $row->last_date,
                'age_days' => $ageDays,
                'supplier_name' => $row->suppliers ?: '-',
                'fishery_name' => $row->fisheries ?: '-',
                'origin_name' => $row->origins ?: '-',
                'contained_types' => array_values(array_unique(array_filter(array_column($articles, 'clean_type')))),
                'warehouses' => $warehouses,
                'primary_warehouse' => $warehouses->first()['name'] ?? '-',
                'initial_weight' => $initialW,
                'remaining_weight' => $remainingW,
                'exited_weight' => $exitedW,
                'consumption_pct' => $pctConsumed,
                'initial_boxes' => (float) $row->initial_boxes,
                'remaining_boxes' => round($remainingBoxes, 2),
                'exited_boxes' => round($exitedBoxes, 2),
                'total_valuation' => round($totalValuation, 2),
                'avg_unit_price' => $remainingW > 0 ? round($totalValuation / $remainingW, 2) : 0,
                'status' => $lotStatus,
                'articles_count' => (int) $row->articles_count,
                'species_list' => $speciesUnique,
                'articles' => $articles,
                'operations_count' => collect($articles)->sum('operations_count'),
            ];
        });

        // 4. Global KPIs summary (Cached alive for real-time responsiveness)
        $cacheKey = 'wh_stock_par_lot_summary_' . md5(json_encode([$warehouseIds, $dateFrom, $dateTo]));
        $summaryCache = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($warehouseIds, $dateFrom, $dateTo) {
            $summaryQuery = DB::table('warehouse_balances')
                ->where('warehouse_operation', 'stock_in')
                ->whereNull('deleted_at')
                ->whereNotNull('reception_tracking_number')
                ->when(!empty($warehouseIds), fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
                ->when(!empty($dateFrom), fn($q) => $q->where('date', '>=', $dateFrom))
                ->when(!empty($dateTo), fn($q) => $q->where('date', '<=', $dateTo));

            $stats = $summaryQuery->selectRaw('
                COUNT(DISTINCT reception_tracking_number) as total_lots,
                COUNT(DISTINCT CASE WHEN remaining_weight > 0 THEN reception_tracking_number END) as in_stock_lots,
                COUNT(DISTINCT CASE WHEN remaining_weight <= 0 THEN reception_tracking_number END) as depleted_lots,
                ROUND(SUM(total_weight), 2) as total_initial_weight,
                ROUND(SUM(remaining_weight), 2) as total_remaining_weight,
                ROUND(SUM(package_count), 2) as total_initial_boxes,
                COUNT(DISTINCT warehouse_id) as occupied_chambers
            ')->first();

            $valRow = DB::table('warehouse_balances')
                ->where('warehouse_operation', 'stock_in')
                ->whereNull('deleted_at')
                ->where('remaining_weight', '>', 0)
                ->when(!empty($warehouseIds), fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
                ->when(!empty($dateFrom), fn($q) => $q->where('date', '>=', $dateFrom))
                ->when(!empty($dateTo), fn($q) => $q->where('date', '<=', $dateTo))
                ->selectRaw('
                    ROUND(SUM(CASE 
                        WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight 
                        ELSE COALESCE(unit_price, 0) * remaining_weight 
                    END), 2) as total_valuation
                ')->first();

            return [
                'stats' => $stats,
                'valuation' => (float) ($valRow->total_valuation ?? 0),
            ];
        });

        $summaryStats = $summaryCache['stats'];
        $totalValuationSum = $summaryCache['valuation'];

        return response()->json([
            'summary' => [
                'total_lots' => (int) ($summaryStats->total_lots ?? 0),
                'in_stock_lots' => (int) ($summaryStats->in_stock_lots ?? 0),
                'depleted_lots' => (int) ($summaryStats->depleted_lots ?? 0),
                'total_initial_weight' => (float) ($summaryStats->total_initial_weight ?? 0),
                'total_remaining_weight' => (float) ($summaryStats->total_remaining_weight ?? 0),
                'total_exited_weight' => max(0, round(((float) ($summaryStats->total_initial_weight ?? 0)) - ((float) ($summaryStats->total_remaining_weight ?? 0)), 2)),
                'total_initial_boxes' => (float) ($summaryStats->total_initial_boxes ?? 0),
                'total_valuation' => round($totalValuationSum, 2),
                'occupied_chambers' => (int) ($summaryStats->occupied_chambers ?? 0),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalLots,
                'last_page' => (int) ceil($totalLots / max(1, $perPage)),
            ],
            'lots' => $transformedLots,
        ]);
    }

    /**
     * Complete Pedigree & Chronological Traceability Timeline for a specific Lot.
     */
    public function lotTraceability(Request $request, $lotNumber)
    {
        $decodedParam = urldecode($lotNumber);
        $cleanParam = self::cleanLotNumber($decodedParam);

        // Break down any comma-separated tokens
        $tokens = array_filter(array_map('trim', explode(',', str_replace(['[', ']', '"', "'"], '', $decodedParam))));
        if (empty($tokens)) {
            $tokens = [$cleanParam];
        }

        $balances = WarehouseBalance::with([
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'warehouse.category',
                'packageType',
                'operations' => fn($q) => $q->orderBy('date', 'asc'),
            ])
            ->where(function ($q) use ($decodedParam, $tokens) {
                $q->where('reception_tracking_number', $decodedParam);
                foreach ($tokens as $tok) {
                    $q->orWhere('reception_tracking_number', 'like', '%"' . $tok . '"%')
                      ->orWhere('reception_tracking_number', 'like', '%' . $tok . '%');
                }
            })
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->orderBy('date', 'asc')
            ->get();

        if ($balances->isEmpty()) {
            return response()->json([
                'message' => 'Numéro de suivi de réception non trouvé',
                'lot_number' => $cleanParam,
                'timeline' => [],
                'articles' => [],
            ], 404);
        }

        $firstBalance = $balances->first();
        $totalInitWeight = (float) $balances->sum('total_weight');
        $totalRemWeight = (float) $balances->sum('remaining_weight');
        $totalExitedWeight = max(0, round($totalInitWeight - $totalRemWeight, 2));
        $totalInitBoxes = (float) $balances->sum('package_count');

        $suppliers = $balances->pluck('supplier_name')->filter()->unique()->values()->all();
        $fisheries = $balances->pluck('fishery_name')->filter()->unique()->values()->all();
        $origins = $balances->pluck('origin_name')->filter()->unique()->values()->all();
        $chambers = $balances->map(fn($b) => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id))->unique()->values()->all();

        $firstDate = $balances->min('date');
        $ageDays = $firstDate ? (int) round(Carbon::parse($firstDate)->diffInDays(now())) : 0;

        $timeline = [];

        // 1. INFLOW EVENTS
        foreach ($balances as $b) {
            $sourceType = class_basename($b->source_type);
            $eventAction = match ($sourceType) {
                'DetailCharge' => 'inflow_charge',
                'DetailProduction' => 'inflow_production',
                'WarehouseTransfer', 'DetailWarehouseTransfer' => 'inflow_transfer',
                'DetailInventory', 'Inventory' => 'inflow_inventory',
                default => 'inflow_default',
            };
            $eventTitle = match ($sourceType) {
                'DetailCharge' => 'Entrée Réception / Charge #' . $b->source_id,
                'DetailProduction' => 'Entrée Production #' . $b->source_id,
                'WarehouseTransfer', 'DetailWarehouseTransfer' => 'Entrée Transfert Inter-Chambres #' . $b->source_id,
                'DetailInventory', 'Inventory' => 'Entrée Calibrage Inventaire #' . $b->source_id,
                default => 'Entrée en Stock #' . $b->id,
            };

            $timeline[] = [
                'type' => 'inflow',
                'event_action' => $eventAction,
                'icon' => 'mdi-arrow-down-bold-circle',
                'color' => '#00897B',
                'date' => $b->date ? $b->date->format('Y-m-d') : null,
                'title' => $eventTitle,
                'source_type' => $sourceType,
                'source_id' => $b->source_id ?: $b->id,
                'warehouse' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                'partner' => implode(' • ', array_filter([$b->supplier_name, $b->fishery_name, $b->origin_name])) ?: (($sourceType === 'DetailInventory' || $sourceType === 'Inventory') ? 'Inventaire' : 'Usine'),
                'article' => $b->article_name,
                'clean_type' => $b->clean_stockable_type,
                'stockable_id' => $b->stockable_id,
                'weight' => (float) $b->total_weight,
                'boxes' => (float) $b->package_count,
                'unit_price' => (float) $b->unit_price,
                'total_price' => (float) $b->total_price,
                'is_inflow' => true,
            ];
        }

        // 2. OUTFLOW EVENTS
        foreach ($balances as $b) {
            foreach ($b->operations as $op) {
                $opSrc = class_basename($op->source_type);
                $isTransfer = str_contains($opSrc, 'Transfer');
                $isProduction = str_contains($opSrc, 'Production');
                $isShipping = str_contains($opSrc, 'Shipping');
                $isReset = str_contains($opSrc, 'Reset');

                $opAction = match (true) {
                    $isShipping => 'outflow_shipping',
                    $isProduction => 'outflow_production',
                    $isTransfer => 'outflow_transfer',
                    $isReset => 'outflow_reset',
                    default => 'outflow_default',
                };

                $opTitle = match (true) {
                    $isShipping => 'Expédition / Vente Client #' . $op->source_id,
                    $isProduction => 'Consommation Atelier Production #' . $op->source_id,
                    $isTransfer => 'Transfert Sortant Chambre #' . $op->source_id,
                    $isReset => 'Ajustement Réinitialisation Stock',
                    default => 'Sortie de Stock #' . $op->id,
                };

                $timeline[] = [
                    'type' => $isTransfer ? 'transfer' : ($isReset ? 'calibration' : 'outflow'),
                    'event_action' => $opAction,
                    'icon' => $isTransfer ? 'mdi-swap-horizontal-bold' : ($isReset ? 'mdi-scale-balance' : 'mdi-arrow-up-bold-circle'),
                    'color' => $isTransfer ? '#F9A825' : ($isReset ? '#6D4C41' : '#D32F2F'),
                    'date' => $op->date ? $op->date->format('Y-m-d') : null,
                    'title' => $opTitle,
                    'source_type' => $opSrc,
                    'source_id' => $op->source_id ?: $op->id,
                    'warehouse' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                    'partner' => $op->client_name ?: ($isProduction ? 'Atelier de Traitement' : 'Sortie'),
                    'article' => $b->article_name,
                    'clean_type' => $b->clean_stockable_type,
                    'stockable_id' => $b->stockable_id,
                    'weight' => (float) $op->weight,
                    'boxes' => (float) $op->package_count,
                    'unit_price' => (float) $op->unit_price,
                    'total_price' => (float) $op->total_price,
                    'is_inflow' => false,
                ];
            }
        }

        usort($timeline, function ($a, $b) {
            return strcmp($a['date'] ?? '', $b['date'] ?? '');
        });

        $status = 'in_stock';
        if ($totalRemWeight <= 0) {
            $status = 'depleted';
        } elseif ($totalRemWeight < $totalInitWeight) {
            $status = 'partially_out';
        }

        $articles = $balances->map(function ($b) {
            $wap = (float) $b->wap;
            $effP = (float) $b->effective_total_price ?: round((float) ($b->unit_price ?? 0) * (float) $b->remaining_weight, 2);
            $outPkgs = (float) $b->operations->sum('package_count');
            return [
                'id' => $b->id,
                'article_name' => $b->article_name,
                'clean_type' => $b->clean_stockable_type,
                'warehouse_name' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                'initial_weight' => (float) $b->total_weight,
                'remaining_weight' => (float) $b->remaining_weight,
                'exited_weight' => max(0, round((float) $b->total_weight - (float) $b->remaining_weight, 2)),
                'initial_boxes' => (float) $b->package_count,
                'remaining_boxes' => max(0, (float) $b->package_count - $outPkgs),
                'wap' => $wap,
                'total_price' => $effP,
                'unit_price' => (float) $b->unit_price,
            ];
        })->values();

        return response()->json([
            'lot_number' => $cleanParam,
            'raw_lot' => $firstBalance->reception_tracking_number,
            'status' => $status,
            'summary' => [
                'first_date' => $firstDate ? Carbon::parse($firstDate)->format('Y-m-d') : null,
                'age_days' => $ageDays,
                'initial_weight' => $totalInitWeight,
                'remaining_weight' => $totalRemWeight,
                'exited_weight' => $totalExitedWeight,
                'consumption_pct' => $totalInitWeight > 0 ? round(($totalExitedWeight / $totalInitWeight) * 100, 1) : 0,
                'initial_boxes' => $totalInitBoxes,
                'remaining_boxes' => round($articles->sum('remaining_boxes'), 2),
                'total_valuation' => round($articles->sum('total_price'), 2),
                'suppliers' => $suppliers,
                'fisheries' => $fisheries,
                'origins' => $origins,
                'chambers' => $chambers,
            ],
            'timeline' => $timeline,
            'articles' => $articles,
        ]);
    }

    /**
     * Traceability Lots: Returns reception tracking numbers filtered by article types (FishType, GuanoType, PackageType).
     * Used by the Traceability & Chronological Tracking view.
     */
    public function traceabilityLots(Request $request)
    {
        $stockableTypes = $request->input('stockable_types', []);
        if (is_string($stockableTypes)) {
            $stockableTypes = array_filter(explode(',', $stockableTypes));
        }
        $warehouseIds = $request->input('warehouse_ids', []);
        if (is_string($warehouseIds)) {
            $warehouseIds = array_filter(explode(',', $warehouseIds));
        }
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all'); // 'all', 'in_stock', 'depleted'

        $query = DB::table('warehouse_balances')
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->whereNotNull('reception_tracking_number')
            ->where('reception_tracking_number', '!=', '')
            ->where('reception_tracking_number', '!=', '[]')
            ->where('reception_tracking_number', '!=', '[""]');

        if (!empty($warehouseIds)) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        if (!empty($stockableTypes)) {
            $query->where(function ($q) use ($stockableTypes) {
                foreach ($stockableTypes as $st) {
                    $q->orWhere('stockable_type', 'like', "%{$st}%");
                }
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reception_tracking_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('fishery_name', 'like', "%{$search}%");
            });
        }

        $gcSuppliers = self::groupConcat('supplier_name', ', ');
        $gcStockTypes = self::groupConcat('stockable_type', ', ');

        $query->selectRaw("
            reception_tracking_number,
            COUNT(*) as articles_count,
            MIN(date) as first_date,
            MAX(date) as last_date,
            ROUND(SUM(total_weight), 2) as initial_weight,
            ROUND(SUM(remaining_weight), 2) as remaining_weight,
            ROUND(SUM(package_count), 2) as initial_boxes,
            {$gcSuppliers} as suppliers,
            {$gcStockTypes} as stockable_types_raw,
            SUM(CASE WHEN remaining_weight > 0 THEN 1 ELSE 0 END) as active_sub_count
        ")->groupBy('reception_tracking_number');

        if ($status === 'in_stock') {
            $query->havingRaw('remaining_weight > 0 OR (initial_boxes > 0 AND active_sub_count > 0)');
        } elseif ($status === 'depleted') {
            $query->havingRaw('remaining_weight <= 0 AND active_sub_count = 0');
        } elseif ($status === 'partially_out') {
            $query->havingRaw('remaining_weight > 0 AND remaining_weight < initial_weight');
        }

        $lots = $query->orderByDesc('first_date')->limit(350)->get();

        $result = $lots->map(function ($l) {
            $cleanLot = self::cleanLotNumber($l->reception_tracking_number);
            $types = [];
            if (str_contains($l->stockable_types_raw, 'FishType')) $types[] = 'FishType';
            if (str_contains($l->stockable_types_raw, 'GuanoType')) $types[] = 'GuanoType';
            if (str_contains($l->stockable_types_raw, 'PackageType')) $types[] = 'PackageType';

            $st = 'in_stock';
            if ($l->remaining_weight <= 0 && $l->active_sub_count == 0) {
                $st = 'depleted';
            } elseif ($l->remaining_weight < $l->initial_weight) {
                $st = 'partially_out';
            }

            return [
                'lot_number' => $cleanLot,
                'raw_lot' => $l->reception_tracking_number,
                'first_date' => $l->first_date,
                'supplier_name' => $l->suppliers ?: '-',
                'initial_weight' => (float) $l->initial_weight,
                'remaining_weight' => (float) $l->remaining_weight,
                'initial_boxes' => (float) $l->initial_boxes,
                'articles_count' => (int) $l->articles_count,
                'types' => $types,
                'status' => $st,
            ];
        });

        return response()->json(['lots' => $result]);
    }

    /**
     * Search Entrance Vouchers (Stage 1 of Chronological Traceability)
     * Finds distinct Stock IN entrance vouchers (Charges, Productions, Inventories)
     * containing the requested reception tracking number(s).
     */
    public function searchTraceabilityVouchers(Request $request)
    {
        $rawInput = $request->input('tracking_numbers', '');
        if (is_array($rawInput)) {
            $tokens = $rawInput;
        } else {
            $tokens = array_filter(array_map('trim', explode(',', str_replace(['[', ']', '"', "'"], '', (string)$rawInput))));
        }
        $tokens = array_values(array_unique(array_filter($tokens)));

        $statusFilter = $request->input('status', 'all'); // 'all', 'in_stock', 'depleted', 'partially_out'
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = WarehouseBalance::with([
                'warehouse.category',
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'packageType',
            ])
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at');

        if (!empty($tokens)) {
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $tok) {
                    $cleaned = self::cleanLotNumber($tok);
                    $q->orWhere('reception_tracking_number', $tok)
                      ->orWhere('reception_tracking_number', $cleaned)
                      ->orWhere('reception_tracking_number', 'like', '%"' . $tok . '"%')
                      ->orWhere('reception_tracking_number', 'like', '%"' . $cleaned . '"%')
                      ->orWhere('reception_tracking_number', 'like', '%' . $tok . '%')
                      ->orWhere('reception_tracking_number', 'like', '%' . $cleaned . '%');
                }
            });
        } else {
            $query->whereNotNull('reception_tracking_number')
                  ->where('reception_tracking_number', '!=', '')
                  ->where('reception_tracking_number', '!=', '[]')
                  ->where('reception_tracking_number', '!=', '[""]');
        }

        if (!empty($dateFrom)) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $balances = $query->orderBy('date', 'desc')->limit(600)->get();

        if ($balances->isEmpty()) {
            return response()->json([
                'vouchers' => [],
                'total_vouchers' => 0,
                'tokens' => $tokens,
            ]);
        }

        // Eager load parents
        $detailChargeIds = $balances->filter(fn($b) => str_contains($b->source_type, 'DetailCharge'))->pluck('source_id')->unique()->all();
        $detailProductionIds = $balances->filter(fn($b) => str_contains($b->source_type, 'DetailProduction'))->pluck('source_id')->unique()->all();
        $detailInventoryIds = $balances->filter(fn($b) => str_contains($b->source_type, 'Inventory'))->pluck('source_id')->unique()->all();
        $detailTransferIds = $balances->filter(fn($b) => str_contains($b->source_type, 'Transfer'))->pluck('source_id')->unique()->all();

        $detailCharges = !empty($detailChargeIds) ? DetailCharge::with(['charge.supplier'])->whereIn('id', $detailChargeIds)->get()->keyBy('id') : collect();
        $detailProductions = !empty($detailProductionIds) ? DetailProduction::with(['production'])->whereIn('id', $detailProductionIds)->get()->keyBy('id') : collect();
        $detailInventories = !empty($detailInventoryIds) ? DetailInventory::with(['inventory'])->whereIn('id', $detailInventoryIds)->get()->keyBy('id') : collect();
        $detailTransfers = !empty($detailTransferIds) ? DetailWarehouseTransfer::with(['warehouseTransfer'])->whereIn('id', $detailTransferIds)->get()->keyBy('id') : collect();

        $vouchers = [];

        foreach ($balances as $b) {
            $voucherType = 'Stock';
            $voucherNumber = $b->source_id ?: $b->id;
            $parentId = $b->source_id;
            $dateStr = $b->date ? $b->date->format('Y-m-d') : 'Sans date';
            $supplier = $b->supplier_name ?: 'N/A';
            $fishery = $b->fishery_name ?: '';
            $boat = $b->origin_name ?: '';

            if (str_contains($b->source_type, 'DetailCharge')) {
                $dc = $detailCharges[$b->source_id] ?? null;
                $parentId = $dc?->charge_id ?? $b->source_id;
                $voucherType = 'Charge';
                $voucherNumber = $dc?->charge?->bon_reception ?: $parentId;
                if ($dc?->charge?->supplier?->name) {
                    $supplier = $dc->charge->supplier->name;
                }
                $key = "charge_{$parentId}_{$dateStr}";
            } elseif (str_contains($b->source_type, 'DetailProduction')) {
                $dp = $detailProductions[$b->source_id] ?? null;
                $parentId = $dp?->production_id ?? $b->source_id;
                $voucherType = 'Production';
                $voucherNumber = $dp?->production?->num_bon ?: $parentId;
                $key = "production_{$parentId}_{$dateStr}";
            } elseif (str_contains($b->source_type, 'Inventory')) {
                $di = $detailInventories[$b->source_id] ?? null;
                $parentId = $di?->inventory_id ?? $b->source_id;
                $voucherType = 'Inventory';
                $voucherNumber = $parentId;
                $supplier = 'Inventaire / Calibrage';
                $key = "inventory_{$parentId}_{$dateStr}";
            } elseif (str_contains($b->source_type, 'Transfer')) {
                $dt = $detailTransfers[$b->source_id] ?? null;
                $parentId = $dt?->warehouse_transfer_id ?? $b->source_id;
                $voucherType = 'Transfer';
                $voucherNumber = $dt?->warehouseTransfer?->transfer_number ?: $parentId;
                $supplier = 'Transfert Inter-Chambres';
                $key = "transfer_{$parentId}_{$dateStr}";
            } else {
                $srcBase = class_basename($b->source_type);
                $voucherType = $srcBase;
                $key = "{$srcBase}_{$parentId}_{$dateStr}";
            }

            if (!isset($vouchers[$key])) {
                $vouchers[$key] = [
                    'key' => $key,
                    'source_type' => $b->source_type,
                    'voucher_type' => $voucherType,
                    'voucher_id' => $parentId,
                    'voucher_number' => (string) $voucherNumber,
                    'date' => $dateStr,
                    'supplier_name' => $supplier,
                    'fishery_name' => $fishery,
                    'boat_name' => $boat,
                    'total_weight' => 0,
                    'remaining_weight' => 0,
                    'total_boxes' => 0,
                    'remaining_boxes' => 0,
                    'articles_map' => [],
                    'balance_ids' => [],
                    'tracking_numbers' => [],
                    'chambers' => [],
                ];
            }

            $vouchers[$key]['total_weight'] += (float) $b->total_weight;
            $vouchers[$key]['remaining_weight'] += (float) $b->remaining_weight;
            $vouchers[$key]['total_boxes'] += (float) $b->package_count;
            $vouchers[$key]['balance_ids'][] = $b->id;

            $lotStr = self::cleanLotNumber($b->reception_tracking_number);
            if ($lotStr && !in_array($lotStr, $vouchers[$key]['tracking_numbers'])) {
                $vouchers[$key]['tracking_numbers'][] = $lotStr;
            }

            $chamberName = $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id);
            if ($chamberName && !in_array($chamberName, $vouchers[$key]['chambers'])) {
                $vouchers[$key]['chambers'][] = $chamberName;
            }

            $artName = $b->article_name ?: ('Article #' . $b->stockable_id);
            if (!isset($vouchers[$key]['articles_map'][$artName])) {
                $vouchers[$key]['articles_map'][$artName] = [
                    'name' => $artName,
                    'type' => $b->clean_stockable_type,
                    'count' => 0,
                    'weight' => 0,
                    'remaining' => 0,
                ];
            }
            $vouchers[$key]['articles_map'][$artName]['count']++;
            $vouchers[$key]['articles_map'][$artName]['weight'] += (float) $b->total_weight;
            $vouchers[$key]['articles_map'][$artName]['remaining'] += (float) $b->remaining_weight;
        }

        $finalVouchers = [];
        foreach ($vouchers as $v) {
            $tot = round($v['total_weight'], 2);
            $rem = round($v['remaining_weight'], 2);
            $exited = max(0, round($tot - $rem, 2));

            $st = 'in_stock';
            if ($rem <= 0) {
                $st = 'depleted';
            } elseif ($rem < $tot) {
                $st = 'partially_out';
            }

            if ($statusFilter !== 'all' && $statusFilter !== $st) {
                continue;
            }

            $v['total_weight'] = $tot;
            $v['remaining_weight'] = $rem;
            $v['exited_weight'] = $exited;
            $v['total_boxes'] = round($v['total_boxes'], 2);
            $v['status'] = $st;
            $v['consumption_pct'] = $tot > 0 ? round(($exited / $tot) * 100, 1) : 0;
            $v['articles_count'] = count($v['articles_map']);
            $v['articles_preview'] = array_values(array_map(fn($a) => $a['name'], $v['articles_map']));
            $v['article_types'] = array_values(array_unique(array_map(fn($a) => $a['type'], $v['articles_map'])));
            unset($v['articles_map']);

            $finalVouchers[] = $v;
        }

        usort($finalVouchers, fn($a, $b) => strcmp($b['date'], $a['date']));

        return response()->json([
            'vouchers' => $finalVouchers,
            'total_vouchers' => count($finalVouchers),
            'tokens' => $tokens,
        ]);
    }

    /**
     * Voucher Articles (Stage 2 of Chronological Traceability)
     * Returns individual article breakdown for a selected voucher's balances.
     */
    public function traceabilityVoucherArticles(Request $request)
    {
        $balanceIds = $request->input('balance_ids', []);
        if (is_string($balanceIds)) {
            $balanceIds = array_filter(explode(',', $balanceIds));
        }

        if (empty($balanceIds)) {
            return response()->json(['articles' => []]);
        }

        $balances = WarehouseBalance::with([
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'warehouse.category',
                'packageType',
                'operations',
            ])
            ->whereIn('id', $balanceIds)
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        $articles = $balances->map(function ($b) {
            $outPkgs = (float) $b->operations->sum('package_count');
            $effP = (float) $b->effective_total_price ?: round((float) ($b->unit_price ?? 0) * (float) $b->remaining_weight, 2);
            $totW = (float) $b->total_weight;
            $remW = (float) $b->remaining_weight;
            $exitedW = max(0, round($totW - $remW, 2));

            $st = 'in_stock';
            if ($remW <= 0) {
                $st = 'depleted';
            } elseif ($remW < $totW) {
                $st = 'partially_out';
            }

            return [
                'id' => $b->id,
                'article_name' => $b->article_name,
                'clean_type' => $b->clean_stockable_type,
                'stockable_id' => $b->stockable_id,
                'reception_tracking_number' => self::cleanLotNumber($b->reception_tracking_number),
                'warehouse_id' => $b->warehouse_id,
                'warehouse_name' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                'date' => $b->date ? $b->date->format('Y-m-d') : null,
                'initial_weight' => $totW,
                'remaining_weight' => $remW,
                'exited_weight' => $exitedW,
                'initial_boxes' => (float) $b->package_count,
                'remaining_boxes' => max(0, round((float) $b->package_count - $outPkgs, 2)),
                'unit_price' => (float) $b->unit_price,
                'wap' => (float) $b->wap,
                'total_price' => $effP,
                'status' => $st,
                'supplier_name' => $b->supplier_name,
            ];
        })->values();

        return response()->json(['articles' => $articles]);
    }

    /**
     * Pedigree & Chronological Timeline for Selected Articles (Stage 3)
     */
    public function traceabilityPedigreeTimeline(Request $request)
    {
        $balanceIds = $request->input('balance_ids', []);
        if (is_string($balanceIds)) {
            $balanceIds = array_filter(explode(',', $balanceIds));
        }

        if (empty($balanceIds)) {
            return response()->json([
                'timeline' => [],
                'summary' => null,
            ], 400);
        }

        $balances = WarehouseBalance::with([
                'stockable' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
                    ]);
                },
                'warehouse.category',
                'packageType',
                'operations' => fn($q) => $q->orderBy('date', 'asc'),
            ])
            ->whereIn('id', $balanceIds)
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->orderBy('date', 'asc')
            ->get();

        if ($balances->isEmpty()) {
            return response()->json([
                'timeline' => [],
                'summary' => null,
            ], 404);
        }

        $totalInitWeight = (float) $balances->sum('total_weight');
        $totalRemWeight = (float) $balances->sum('remaining_weight');
        $totalExitedWeight = max(0, round($totalInitWeight - $totalRemWeight, 2));
        $totalInitBoxes = (float) $balances->sum('package_count');

        $suppliers = $balances->pluck('supplier_name')->filter()->unique()->values()->all();
        $fisheries = $balances->pluck('fishery_name')->filter()->unique()->values()->all();
        $origins = $balances->pluck('origin_name')->filter()->unique()->values()->all();
        $chambers = $balances->map(fn($b) => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id))->unique()->values()->all();
        $trackingNumbers = $balances->map(fn($b) => self::cleanLotNumber($b->reception_tracking_number))->filter()->unique()->values()->all();

        $firstDate = $balances->min('date');
        $lastDate = $balances->max('date');
        $ageDays = $firstDate ? (int) round(Carbon::parse($firstDate)->diffInDays(now())) : 0;

        $timeline = [];

        // 1. Inflow Events
        foreach ($balances as $b) {
            $sourceType = class_basename($b->source_type);
            $eventAction = match ($sourceType) {
                'DetailCharge' => 'inflow_charge',
                'DetailProduction' => 'inflow_production',
                'WarehouseTransfer', 'DetailWarehouseTransfer' => 'inflow_transfer',
                'DetailInventory', 'Inventory' => 'inflow_inventory',
                default => 'inflow_default',
            };
            $eventTitle = match ($sourceType) {
                'DetailCharge' => 'Entrée Réception / Charge #' . $b->source_id,
                'DetailProduction' => 'Entrée Production #' . $b->source_id,
                'WarehouseTransfer', 'DetailWarehouseTransfer' => 'Entrée Transfert Inter-Chambres #' . $b->source_id,
                'DetailInventory', 'Inventory' => 'Entrée Calibrage Inventaire #' . $b->source_id,
                default => 'Entrée en Stock #' . $b->id,
            };

            $timeline[] = [
                'event_id' => 'in_' . $b->id,
                'type' => 'inflow',
                'event_action' => $eventAction,
                'icon' => 'mdi-arrow-down-bold-circle',
                'color' => '#00897B',
                'date' => $b->date ? $b->date->format('Y-m-d') : null,
                'title' => $eventTitle,
                'source_type' => $sourceType,
                'source_id' => $b->source_id ?: $b->id,
                'warehouse' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                'partner' => implode(' • ', array_filter([$b->supplier_name, $b->fishery_name, $b->origin_name])) ?: (($sourceType === 'DetailInventory' || $sourceType === 'Inventory') ? 'Inventaire' : 'Usine'),
                'article' => $b->article_name,
                'clean_type' => $b->clean_stockable_type,
                'stockable_id' => $b->stockable_id,
                'lot_number' => self::cleanLotNumber($b->reception_tracking_number),
                'weight' => (float) $b->total_weight,
                'boxes' => (float) $b->package_count,
                'unit_price' => (float) $b->unit_price,
                'total_price' => (float) $b->total_price,
                'is_inflow' => true,
            ];
        }

        // 2. Outflow / Movements
        $allClients = [];
        foreach ($balances as $b) {
            foreach ($b->operations as $op) {
                $opSrc = class_basename($op->source_type);
                $isTransfer = str_contains($opSrc, 'Transfer');
                $isProduction = str_contains($opSrc, 'Production');
                $isShipping = str_contains($opSrc, 'Shipping');
                $isReset = str_contains($opSrc, 'Reset');

                if ($op->client_name) {
                    $allClients[] = $op->client_name;
                }

                $opAction = match (true) {
                    $isShipping => 'outflow_shipping',
                    $isProduction => 'outflow_production',
                    $isTransfer => 'outflow_transfer',
                    $isReset => 'outflow_reset',
                    default => 'outflow_default',
                };

                $opTitle = match (true) {
                    $isShipping => 'Expédition / Vente Client #' . $op->source_id,
                    $isProduction => 'Consommation Atelier Production #' . $op->source_id,
                    $isTransfer => 'Transfert Sortant Chambre #' . $op->source_id,
                    $isReset => 'Ajustement Réinitialisation Stock',
                    default => 'Sortie de Stock #' . $op->id,
                };

                $timeline[] = [
                    'event_id' => 'op_' . $op->id,
                    'type' => $isTransfer ? 'transfer' : ($isReset ? 'calibration' : 'outflow'),
                    'event_action' => $opAction,
                    'icon' => $isTransfer ? 'mdi-swap-horizontal-bold' : ($isReset ? 'mdi-scale-balance' : ($isShipping ? 'mdi-truck-fast' : 'mdi-factory')),
                    'color' => $isTransfer ? '#F9A825' : ($isReset ? '#6D4C41' : ($isShipping ? '#D32F2F' : '#6c66bb')),
                    'date' => $op->date ? $op->date->format('Y-m-d') : null,
                    'title' => $opTitle,
                    'source_type' => $opSrc,
                    'source_id' => $op->source_id ?: $op->id,
                    'warehouse' => $b->warehouse->name ?? ('Chambre #' . $b->warehouse_id),
                    'partner' => $op->client_name ?: ($isProduction ? 'Atelier de Traitement' : ($isTransfer ? 'Transfert Interne' : 'Sortie')),
                    'article' => $b->article_name,
                    'clean_type' => $b->clean_stockable_type,
                    'stockable_id' => $b->stockable_id,
                    'lot_number' => self::cleanLotNumber($b->reception_tracking_number),
                    'weight' => (float) $op->weight,
                    'boxes' => (float) $op->package_count,
                    'unit_price' => (float) $op->unit_price,
                    'total_price' => (float) $op->total_price,
                    'is_inflow' => false,
                ];
            }
        }

        usort($timeline, function ($a, $b) {
            return strcmp($a['date'] ?? '', $b['date'] ?? '');
        });

        $status = 'in_stock';
        if ($totalRemWeight <= 0) {
            $status = 'depleted';
        } elseif ($totalRemWeight < $totalInitWeight) {
            $status = 'partially_out';
        }

        $allClients = array_values(array_unique(array_filter($allClients)));

        return response()->json([
            'status' => $status,
            'summary' => [
                'tracking_numbers' => $trackingNumbers,
                'first_date' => $firstDate ? Carbon::parse($firstDate)->format('Y-m-d') : null,
                'last_date' => $lastDate ? Carbon::parse($lastDate)->format('Y-m-d') : null,
                'age_days' => $ageDays,
                'initial_weight' => $totalInitWeight,
                'remaining_weight' => $totalRemWeight,
                'exited_weight' => $totalExitedWeight,
                'consumption_pct' => $totalInitWeight > 0 ? round(($totalExitedWeight / $totalInitWeight) * 100, 1) : 0,
                'initial_boxes' => $totalInitBoxes,
                'remaining_boxes' => max(0, round($totalInitBoxes - (float) $balances->sum(fn($b) => $b->operations->sum('package_count')), 2)),
                'total_valuation' => round($balances->sum(fn($b) => (float) ($b->effective_total_price ?: ($b->unit_price * $b->remaining_weight))), 2),
                'suppliers' => $suppliers,
                'fisheries' => $fisheries,
                'origins' => $origins,
                'chambers' => $chambers,
                'clients' => $allClients,
                'articles_count' => $balances->count(),
            ],
            'timeline' => $timeline,
        ]);
    }

    /**
     * Stock Movements Journal: Unified chronological stream of stock ins, outs, and transfers.
     */
    public function stockMovementsJournal(Request $request)
    {
        $warehouseIds = $request->input('warehouse_ids', []);
        if (is_string($warehouseIds)) {
            $warehouseIds = array_filter(explode(',', $warehouseIds));
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = trim($request->input('search', ''));
        $movementType = $request->input('movement_type', 'all'); // 'in', 'out', 'all'
        $noPagination = $request->boolean('no_pagination', true) || $request->input('per_page') == -1;
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(500, max(10, (int) $request->input('per_page', 50)));

        // INFLOWS Query
        $inflowsQuery = DB::table('warehouse_balances as wb')
            ->where('wb.warehouse_operation', 'stock_in')
            ->whereNull('wb.deleted_at')
            ->selectRaw('
                wb.id as movement_id,
                wb.date as movement_date,
                "IN" as direction,
                wb.source_type,
                wb.source_id,
                wb.reception_tracking_number,
                wb.stockable_type,
                wb.stockable_id,
                wb.warehouse_id,
                wb.total_weight as weight,
                wb.package_count as boxes,
                wb.unit_price,
                wb.total_price,
                wb.supplier_name as partner_name,
                wb.fishery_name,
                wb.origin_name
            ');

        // OUTFLOWS Query
        $outflowsQuery = DB::table('warehouse_balance_operations as wbo')
            ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
            ->whereNull('wbo.deleted_at')
            ->whereNull('wb.deleted_at')
            ->selectRaw('
                wbo.id as movement_id,
                wbo.date as movement_date,
                "OUT" as direction,
                wbo.source_type,
                wbo.source_id,
                wb.reception_tracking_number,
                wb.stockable_type,
                wb.stockable_id,
                wb.warehouse_id,
                wbo.weight,
                wbo.package_count as boxes,
                wbo.unit_price,
                wbo.total_price,
                wbo.client_name as partner_name,
                wb.fishery_name,
                wb.origin_name
            ');

        if (!empty($warehouseIds)) {
            $inflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
            $outflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
        }
        if (!empty($dateFrom)) {
            $inflowsQuery->where('wb.date', '>=', $dateFrom);
            $outflowsQuery->where('wbo.date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $inflowsQuery->where('wb.date', '<=', $dateTo);
            $outflowsQuery->where('wbo.date', '<=', $dateTo);
        }
        if (!empty($search)) {
            $inflowsQuery->where(function ($q) use ($search) {
                $q->where('wb.reception_tracking_number', 'like', "%{$search}%")
                  ->orWhere('wb.supplier_name', 'like', "%{$search}%");
            });
            $outflowsQuery->where(function ($q) use ($search) {
                $q->where('wb.reception_tracking_number', 'like', "%{$search}%")
                  ->orWhere('wbo.client_name', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($request->input('limit') ?: ($noPagination ? 200 : $perPage));

        // 1. Separate fast totals without disk temp table
        $inWeight = 0; $outWeight = 0; $inBoxes = 0; $outBoxes = 0; $totalMovements = 0;
        if ($movementType !== 'out') {
            $inStats = (clone $inflowsQuery)->select(DB::raw('COUNT(*) as total_count, COALESCE(SUM(wb.total_weight), 0) as total_weight, COALESCE(SUM(wb.package_count), 0) as total_boxes'))->first();
            $inWeight = (float) ($inStats->total_weight ?? 0);
            $inBoxes = (float) ($inStats->total_boxes ?? 0);
            $totalMovements += (int) ($inStats->total_count ?? 0);
        }
        if ($movementType !== 'in') {
            $outStats = (clone $outflowsQuery)->select(DB::raw('COUNT(*) as total_count, COALESCE(SUM(wbo.weight), 0) as total_weight, COALESCE(SUM(wbo.package_count), 0) as total_boxes'))->first();
            $outWeight = (float) ($outStats->total_weight ?? 0);
            $outBoxes = (float) ($outStats->total_boxes ?? 0);
            $totalMovements += (int) ($outStats->total_count ?? 0);
        }

        // 2. Fetch rows efficiently using index-backed limits
        if ($noPagination || $page === 1) {
            $inRows = collect([]);
            if ($movementType !== 'out') {
                $inRows = (clone $inflowsQuery)->orderBy('wb.date', 'desc')->limit($limit)->get();
            }
            $outRows = collect([]);
            if ($movementType !== 'in') {
                $outRows = (clone $outflowsQuery)->orderBy('wbo.date', 'desc')->limit($limit)->get();
            }
            $rows = $inRows->concat($outRows)->sortByDesc('movement_date')->take($limit)->values();
        } else {
            $unionQuery = ($movementType === 'in') ? $inflowsQuery : (($movementType === 'out') ? $outflowsQuery : $inflowsQuery->unionAll($outflowsQuery));
            $query = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
                ->mergeBindings($unionQuery)
                ->orderBy('movement_date', 'desc');
            $rows = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        }

        $fishNames = \App\Models\FishType::pluck('name_fr', 'id');
        $guanoNames = \App\Models\GuanoType::pluck('name_fr', 'id');
        $pkgNames = \App\Models\PackageType::pluck('name_fr', 'id');
        $whNames = Warehouse::pluck('name', 'id');

        $movements = $rows->map(function ($row) use ($fishNames, $guanoNames, $pkgNames, $whNames) {
            $artName = 'Article #' . $row->stockable_id;
            if (str_contains($row->stockable_type, 'FishType')) {
                $artName = $fishNames[$row->stockable_id] ?? $artName;
            } elseif (str_contains($row->stockable_type, 'GuanoType')) {
                $artName = $guanoNames[$row->stockable_id] ?? $artName;
            } elseif (str_contains($row->stockable_type, 'PackageType')) {
                $artName = $pkgNames[$row->stockable_id] ?? $artName;
            }

            return [
                'id' => $row->movement_id,
                'date' => $row->movement_date,
                'direction' => $row->direction,
                'lot_number' => self::cleanLotNumber($row->reception_tracking_number),
                'raw_lot' => $row->reception_tracking_number,
                'source_type' => class_basename($row->source_type),
                'source_id' => $row->source_id,
                'article_name' => $artName,
                'warehouse_id' => $row->warehouse_id,
                'warehouse_name' => $whNames[$row->warehouse_id] ?? ('Chambre #' . $row->warehouse_id),
                'weight' => (float) $row->weight,
                'boxes' => (float) $row->boxes,
                'unit_price' => (float) $row->unit_price,
                'total_price' => (float) $row->total_price,
                'partner_name' => $row->partner_name ?: '-',
            ];
        });

        return response()->json([
            'totals' => [
                'total_count' => $totalMovements,
                'total_in_weight' => round($inWeight, 2),
                'total_out_weight' => round($outWeight, 2),
                'net_weight' => round($inWeight - $outWeight, 2),
                'total_in_boxes' => round($inBoxes, 2),
                'total_out_boxes' => round($outBoxes, 2),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $noPagination ? $totalMovements : $perPage,
                'total' => $totalMovements,
                'last_page' => $noPagination ? 1 : (int) ceil($totalMovements / max(1, $perPage)),
            ],
            'movements' => $movements,
        ]);
    }

    /**
     * Analytics & Charts: deep aggregated statistics for a custom date range.
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->input('date_from', \Carbon\Carbon::now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', \Carbon\Carbon::now()->toDateString());
        $granularity = $request->input('granularity', 'daily'); // 'daily', 'weekly', 'monthly'
        if (!in_array($granularity, ['daily', 'weekly', 'monthly'])) {
            $granularity = 'daily';
        }
        $warehouseIds = (array) $request->input('warehouse_ids', []);
        $warehouseIds = array_filter(array_map('intval', $warehouseIds));

        // 1. Inflows base query
        $inflowsQuery = DB::table('warehouse_balances as wb')
            ->join('warehouses as w', 'w.id', '=', 'wb.warehouse_id')
            ->where('wb.warehouse_operation', 'stock_in')
            ->whereNull('wb.deleted_at')
            ->whereBetween('wb.date', [$dateFrom, $dateTo])
            ->selectRaw('
                wb.id as movement_id,
                DATE(wb.date) as movement_date,
                "IN" as direction,
                wb.reception_tracking_number,
                wb.stockable_type,
                wb.stockable_id,
                wb.warehouse_id,
                w.name as warehouse_name,
                wb.total_weight as weight,
                wb.package_count as boxes,
                wb.unit_price,
                COALESCE(wb.total_price, wb.total_weight * wb.unit_price) as valuation,
                wb.supplier_name as partner_name
            ');

        // 2. Outflows base query
        $outflowsQuery = DB::table('warehouse_balance_operations as wbo')
            ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
            ->join('warehouses as w', 'w.id', '=', 'wb.warehouse_id')
            ->whereNull('wbo.deleted_at')
            ->whereNull('wb.deleted_at')
            ->whereBetween('wbo.date', [$dateFrom, $dateTo])
            ->selectRaw('
                wbo.id as movement_id,
                DATE(wbo.date) as movement_date,
                "OUT" as direction,
                wb.reception_tracking_number,
                wb.stockable_type,
                wb.stockable_id,
                wb.warehouse_id,
                w.name as warehouse_name,
                wbo.weight,
                wbo.package_count as boxes,
                wbo.unit_price,
                COALESCE(wbo.total_price, wbo.weight * wbo.unit_price) as valuation,
                wbo.client_name as partner_name
            ');

        if (!empty($warehouseIds)) {
            $inflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
            $outflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
        }

        $unionQuery = $inflowsQuery->unionAll($outflowsQuery);

        // Summary KPI
        $summaryRow = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->selectRaw("
                COUNT(*) as movements_count,
                SUM(CASE WHEN direction = 'IN' THEN 1 ELSE 0 END) as in_count,
                SUM(CASE WHEN direction = 'OUT' THEN 1 ELSE 0 END) as out_count,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN boxes ELSE 0 END), 0) as in_boxes,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN boxes ELSE 0 END), 0) as out_boxes,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN valuation ELSE 0 END), 0) as in_valuation,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN valuation ELSE 0 END), 0) as out_valuation,
                COUNT(DISTINCT reception_tracking_number) as active_lots_count
            ")->first();

        $inWeight = (float) ($summaryRow->in_weight ?? 0);
        $outWeight = (float) ($summaryRow->out_weight ?? 0);
        $inBoxes = (float) ($summaryRow->in_boxes ?? 0);
        $outBoxes = (float) ($summaryRow->out_boxes ?? 0);
        $inValuation = (float) ($summaryRow->in_valuation ?? 0);
        $outValuation = (float) ($summaryRow->out_valuation ?? 0);
        $daysCount = max(1, \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);

        // Timeline evolution with selectable granularity
        $dateFormatExpr = match ($granularity) {
            'weekly' => "DATE_SUB(DATE(movement_date), INTERVAL WEEKDAY(movement_date) DAY)",
            'monthly' => "DATE_FORMAT(movement_date, '%Y-%m-01')",
            default => "DATE(movement_date)",
        };

        $timelineRows = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->groupBy(DB::raw($dateFormatExpr))
            ->orderBy(DB::raw($dateFormatExpr), 'asc')
            ->selectRaw("
                {$dateFormatExpr} as group_date,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN boxes ELSE 0 END), 0) as in_boxes,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN boxes ELSE 0 END), 0) as out_boxes,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN valuation ELSE 0 END), 0) as in_valuation,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN valuation ELSE 0 END), 0) as out_valuation,
                COUNT(*) as movements_count
            ")->get();

        $cumulativeNet = 0;
        $timeline = $timelineRows->map(function ($r) use (&$cumulativeNet) {
            $inW = (float) $r->in_weight;
            $outW = (float) $r->out_weight;
            $netW = $inW - $outW;
            $cumulativeNet += $netW;
            return [
                'date' => $r->group_date,
                'in_weight' => round($inW, 2),
                'out_weight' => round($outW, 2),
                'net_weight' => round($netW, 2),
                'in_boxes' => round((float) $r->in_boxes, 2),
                'out_boxes' => round((float) $r->out_boxes, 2),
                'in_valuation' => round((float) $r->in_valuation, 2),
                'out_valuation' => round((float) $r->out_valuation, 2),
                'cumulative_net_weight' => round($cumulativeNet, 2),
                'movements_count' => (int) $r->movements_count,
            ];
        });

        // Current Stock Aging Breakdown (FIFO risk & freshness analysis)
        $agingQuery = DB::table('warehouse_balances')
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->where('remaining_weight', '>', 0);
        if (!empty($warehouseIds)) {
            $agingQuery->whereIn('warehouse_id', $warehouseIds);
        }
        $dd = self::dateDiffDays('date');
        $agingRow = $agingQuery->selectRaw("
            COUNT(DISTINCT reception_tracking_number) as total_lots,
            COUNT(*) as total_items,
            ROUND(SUM(remaining_weight), 2) as total_weight,
            ROUND(SUM(CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END), 2) as total_valuation,
            ROUND(AVG({$dd}), 1) as avg_age_days,
            ROUND(SUM(CASE WHEN {$dd} <= 30 THEN remaining_weight ELSE 0 END), 2) as fresh_weight,
            ROUND(SUM(CASE WHEN {$dd} > 30 AND {$dd} <= 60 THEN remaining_weight ELSE 0 END), 2) as optimal_weight,
            ROUND(SUM(CASE WHEN {$dd} > 60 AND {$dd} <= 90 THEN remaining_weight ELSE 0 END), 2) as warning_weight,
            ROUND(SUM(CASE WHEN {$dd} > 90 THEN remaining_weight ELSE 0 END), 2) as alert_weight,
            ROUND(SUM(CASE WHEN {$dd} <= 30 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as fresh_val,
            ROUND(SUM(CASE WHEN {$dd} > 30 AND {$dd} <= 60 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as optimal_val,
            ROUND(SUM(CASE WHEN {$dd} > 60 AND {$dd} <= 90 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as warning_val,
            ROUND(SUM(CASE WHEN {$dd} > 90 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as alert_val,
            COUNT(DISTINCT CASE WHEN {$dd} <= 30 THEN reception_tracking_number END) as fresh_lots,
            COUNT(DISTINCT CASE WHEN {$dd} > 30 AND {$dd} <= 60 THEN reception_tracking_number END) as optimal_lots,
            COUNT(DISTINCT CASE WHEN {$dd} > 60 AND {$dd} <= 90 THEN reception_tracking_number END) as warning_lots,
            COUNT(DISTINCT CASE WHEN {$dd} > 90 THEN reception_tracking_number END) as alert_lots
        ")->first();

        $totalActiveW = (float) ($agingRow->total_weight ?? 1);
        $aging = [
            'total_lots' => (int) ($agingRow->total_lots ?? 0),
            'total_items' => (int) ($agingRow->total_items ?? 0),
            'total_weight' => round((float) ($agingRow->total_weight ?? 0), 2),
            'total_valuation' => round((float) ($agingRow->total_valuation ?? 0), 2),
            'avg_age_days' => (float) ($agingRow->avg_age_days ?? 0),
            'buckets' => [
                'fresh' => [
                    'key' => 'fresh',
                    'label' => '0 - 30 jours (Frais / Récent)',
                    'weight' => (float) ($agingRow->fresh_weight ?? 0),
                    'valuation' => (float) ($agingRow->fresh_val ?? 0),
                    'lots' => (int) ($agingRow->fresh_lots ?? 0),
                    'pct' => round(((float) ($agingRow->fresh_weight ?? 0) / $totalActiveW) * 100, 1),
                    'color' => '#00897B',
                ],
                'optimal' => [
                    'key' => 'optimal',
                    'label' => '31 - 60 jours (Normal)',
                    'weight' => (float) ($agingRow->optimal_weight ?? 0),
                    'valuation' => (float) ($agingRow->optimal_val ?? 0),
                    'lots' => (int) ($agingRow->optimal_lots ?? 0),
                    'pct' => round(((float) ($agingRow->optimal_weight ?? 0) / $totalActiveW) * 100, 1),
                    'color' => '#1E88E5',
                ],
                'warning' => [
                    'key' => 'warning',
                    'label' => '61 - 90 jours (À surveiller)',
                    'weight' => (float) ($agingRow->warning_weight ?? 0),
                    'valuation' => (float) ($agingRow->warning_val ?? 0),
                    'lots' => (int) ($agingRow->warning_lots ?? 0),
                    'pct' => round(((float) ($agingRow->warning_weight ?? 0) / $totalActiveW) * 100, 1),
                    'color' => '#FB8C00',
                ],
                'alert' => [
                    'key' => 'alert',
                    'label' => '> 90 jours (Critique / FIFO)',
                    'weight' => (float) ($agingRow->alert_weight ?? 0),
                    'valuation' => (float) ($agingRow->alert_val ?? 0),
                    'lots' => (int) ($agingRow->alert_lots ?? 0),
                    'pct' => round(((float) ($agingRow->alert_weight ?? 0) / $totalActiveW) * 100, 1),
                    'color' => '#E53935',
                ],
            ],
        ];

        // Species Breakdown
        $fishNames = \App\Models\FishType::pluck('name_fr', 'id');
        $guanoNames = \App\Models\GuanoType::pluck('name_fr', 'id');
        $pkgNames = \App\Models\PackageType::pluck('name_fr', 'id');

        $speciesRows = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->groupBy('stockable_type', 'stockable_id')
            ->selectRaw("
                stockable_type,
                stockable_id,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(weight), 0) as total_flow,
                COALESCE(SUM(boxes), 0) as total_boxes,
                COALESCE(SUM(valuation), 0) as total_valuation
            ")
            ->orderBy('total_flow', 'desc')
            ->limit(20)
            ->get();

        $species = $speciesRows->map(function ($r) use ($fishNames, $guanoNames, $pkgNames, $inWeight, $outWeight) {
            $name = 'Article #' . $r->stockable_id;
            if (str_contains($r->stockable_type, 'FishType')) {
                $name = $fishNames[$r->stockable_id] ?? $name;
            } elseif (str_contains($r->stockable_type, 'GuanoType')) {
                $name = $guanoNames[$r->stockable_id] ?? $name;
            } elseif (str_contains($r->stockable_type, 'PackageType')) {
                $name = $pkgNames[$r->stockable_id] ?? $name;
            }

            $totalAllFlow = ($inWeight + $outWeight) ?: 1;
            $flow = (float) $r->total_flow;
            return [
                'article_name' => $name,
                'stockable_type' => class_basename($r->stockable_type),
                'in_weight' => round((float) $r->in_weight, 2),
                'out_weight' => round((float) $r->out_weight, 2),
                'net_weight' => round((float) $r->in_weight - (float) $r->out_weight, 2),
                'total_flow' => round($flow, 2),
                'total_boxes' => round((float) $r->total_boxes, 2),
                'total_valuation' => round((float) $r->total_valuation, 2),
                'share_pct' => round(($flow / $totalAllFlow) * 100, 1),
            ];
        });

        // Product Family Breakdown
        $familyRows = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->groupBy('stockable_type')
            ->selectRaw("
                stockable_type,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(weight), 0) as total_flow,
                COALESCE(SUM(boxes), 0) as total_boxes,
                COALESCE(SUM(valuation), 0) as total_valuation
            ")
            ->get();

        $families = $familyRows->map(function ($r) use ($inWeight, $outWeight) {
            $base = class_basename($r->stockable_type);
            $label = match ($base) {
                'FishType' => 'Produits de la Mer / Poissons Congelés',
                'PackageType' => 'Emballages & Cartons',
                'GuanoType' => 'Guano',
                default => $base,
            };
            $color = match ($base) {
                'FishType' => '#00897B',
                'PackageType' => '#6D4C41',
                'GuanoType' => '#FB8C00',
                default => '#a21ee5',
            };
            $totalAllFlow = ($inWeight + $outWeight) ?: 1;
            $flow = (float) $r->total_flow;
            return [
                'stockable_type' => $base,
                'label' => $label,
                'color' => $color,
                'in_weight' => round((float) $r->in_weight, 2),
                'out_weight' => round((float) $r->out_weight, 2),
                'net_weight' => round((float) $r->in_weight - (float) $r->out_weight, 2),
                'total_flow' => round($flow, 2),
                'total_boxes' => round((float) $r->total_boxes, 2),
                'total_valuation' => round((float) $r->total_valuation, 2),
                'share_pct' => round(($flow / $totalAllFlow) * 100, 1),
            ];
        });

        // Chambers Activity
        $chambersRows = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->groupBy('warehouse_id', 'warehouse_name')
            ->selectRaw("
                warehouse_id,
                warehouse_name,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(weight), 0) as total_flow,
                COUNT(*) as movements_count
            ")
            ->orderBy('total_flow', 'desc')
            ->get();

        $currentStocks = Warehouse::pluck('total_weight', 'id');
        $totalChambersFlow = ($inWeight + $outWeight) ?: 1;
        $chambers = $chambersRows->map(function ($r) use ($currentStocks, $totalChambersFlow) {
            $inW = (float) $r->in_weight;
            $outW = (float) $r->out_weight;
            $flow = (float) $r->total_flow;
            return [
                'warehouse_id' => $r->warehouse_id,
                'name' => $r->warehouse_name,
                'in_weight' => round($inW, 2),
                'out_weight' => round($outW, 2),
                'net_weight' => round($inW - $outW, 2),
                'total_flow' => round($flow, 2),
                'current_stock' => round((float) ($currentStocks[$r->warehouse_id] ?? 0), 2),
                'activity_pct' => round(($flow / $totalChambersFlow) * 100, 1),
                'movements_count' => (int) $r->movements_count,
            ];
        });

        // Top Partners (Inflow vs Outflow)
        $topSuppliers = DB::table(DB::raw("({$inflowsQuery->toSql()}) as inf"))
            ->mergeBindings($inflowsQuery)
            ->whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->where('partner_name', '!=', '-')
            ->groupBy('partner_name')
            ->selectRaw("
                partner_name,
                COUNT(*) as movements_count,
                COALESCE(SUM(weight), 0) as total_weight,
                COALESCE(SUM(boxes), 0) as total_boxes,
                COALESCE(SUM(valuation), 0) as total_valuation
            ")
            ->orderBy('total_weight', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'partner_name' => $p->partner_name,
                'movements_count' => (int) $p->movements_count,
                'total_weight' => round((float) $p->total_weight, 2),
                'total_boxes' => round((float) $p->total_boxes, 2),
                'total_valuation' => round((float) $p->total_valuation, 2),
            ]);

        $topClients = DB::table(DB::raw("({$outflowsQuery->toSql()}) as outf"))
            ->mergeBindings($outflowsQuery)
            ->whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->where('partner_name', '!=', '-')
            ->groupBy('partner_name')
            ->selectRaw("
                partner_name,
                COUNT(*) as movements_count,
                COALESCE(SUM(weight), 0) as total_weight,
                COALESCE(SUM(boxes), 0) as total_boxes,
                COALESCE(SUM(valuation), 0) as total_valuation
            ")
            ->orderBy('total_weight', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'partner_name' => $p->partner_name,
                'movements_count' => (int) $p->movements_count,
                'total_weight' => round((float) $p->total_weight, 2),
                'total_boxes' => round((float) $p->total_boxes, 2),
                'total_valuation' => round((float) $p->total_valuation, 2),
            ]);

        // Top active Reception Tracking Numbers in period
        $topTrackingRows = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
            ->mergeBindings($unionQuery)
            ->groupBy('reception_tracking_number')
            ->selectRaw("
                reception_tracking_number,
                COUNT(*) as movements_count,
                COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
                COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
                COALESCE(SUM(weight), 0) as total_weight
            ")
            ->orderBy('total_weight', 'desc')
            ->limit(12)
            ->get();

        $activeTracking = $topTrackingRows->map(function ($r) {
            $clean = self::cleanLotNumber($r->reception_tracking_number);
            $inW = (float) $r->in_weight;
            $outW = (float) $r->out_weight;
            return [
                'lot_number' => $clean,
                'raw_lot' => $r->reception_tracking_number,
                'in_weight' => round($inW, 2),
                'out_weight' => round($outW, 2),
                'net_weight' => round($inW - $outW, 2),
                'movements_count' => (int) $r->movements_count,
            ];
        });

        return response()->json([
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'days_count' => $daysCount,
            ],
            'granularity' => $granularity,
            'summary' => [
                'in_weight' => round($inWeight, 2),
                'out_weight' => round($outWeight, 2),
                'net_weight' => round($inWeight - $outWeight, 2),
                'in_boxes' => round($inBoxes, 2),
                'out_boxes' => round($outBoxes, 2),
                'net_boxes' => round($inBoxes - $outBoxes, 2),
                'in_valuation' => round($inValuation, 2),
                'out_valuation' => round($outValuation, 2),
                'net_valuation' => round($inValuation - $outValuation, 2),
                'movements_count' => (int) ($summaryRow->movements_count ?? 0),
                'in_count' => (int) ($summaryRow->in_count ?? 0),
                'out_count' => (int) ($summaryRow->out_count ?? 0),
                'active_lots_count' => (int) ($summaryRow->active_lots_count ?? 0),
            ],
            'ratios' => [
                'destocking_rate' => $inWeight > 0 ? round(($outWeight / $inWeight) * 100, 1) : 0,
                'daily_avg_in' => round($inWeight / $daysCount, 2),
                'daily_avg_out' => round($outWeight / $daysCount, 2),
                'avg_in_price' => $inWeight > 0 ? round($inValuation / $inWeight, 2) : 0,
                'avg_out_price' => $outWeight > 0 ? round($outValuation / $outWeight, 2) : 0,
            ],
            'aging' => $aging,
            'timeline' => $timeline,
            'species' => $species,
            'families' => $families,
            'chambers' => $chambers,
            'partners' => [
                'suppliers' => $topSuppliers,
                'clients' => $topClients,
            ],
            'tracking_numbers' => $activeTracking,
        ]);
    }

    /**
     * Chambers Overview: returns list of cold rooms with active stock, capacity, valuation.
     */
    public function chambersOverview(Request $request)
    {
        $chambers = Warehouse::with('category')->get();

        $activeByWh = DB::table('warehouse_balances')
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->where('remaining_weight', '>', 0)
            ->groupBy('warehouse_id')
            ->selectRaw('
                warehouse_id,
                COUNT(DISTINCT reception_tracking_number) as active_lots,
                COUNT(*) as active_articles,
                ROUND(SUM(remaining_weight), 2) as total_weight,
                ROUND(SUM(package_count), 2) as total_boxes,
                ROUND(SUM(total_price), 2) as est_value
            ')
            ->get()
            ->keyBy('warehouse_id');

        $result = $chambers->map(function ($ch) use ($activeByWh) {
            $stat = $activeByWh[$ch->id] ?? null;
            return [
                'id' => $ch->id,
                'name' => $ch->name,
                'category_name' => $ch->category->name ?? null,
                'warehouse_type' => $ch->warehouse_type,
                'allow_production_effect' => (bool) $ch->allow_production_effect,
                'has_sejour' => (bool) $ch->has_sejour,
                'active_lots' => (int) ($stat->active_lots ?? 0),
                'active_articles' => (int) ($stat->active_articles ?? 0),
                'total_weight' => (float) ($stat->total_weight ?? 0),
                'total_boxes' => (float) ($stat->total_boxes ?? 0),
                'valuation' => (float) ($stat->est_value ?? 0),
                'is_empty' => empty($stat) || (float) $stat->total_weight <= 0,
            ];
        });

        return response()->json(['chambers' => $result]);
    }

    /**
     * Species Overview: returns aggregated stock grouped by fish species / article.
     */
    public function speciesOverview(Request $request)
    {
        $warehouseIds = $request->input('warehouse_ids', []);
        if (is_string($warehouseIds)) {
            $warehouseIds = array_filter(explode(',', $warehouseIds));
        }

        $query = DB::table('warehouse_balances')
            ->where('warehouse_operation', 'stock_in')
            ->whereNull('deleted_at')
            ->where('remaining_weight', '>', 0);

        if (!empty($warehouseIds)) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $gcLots = self::groupConcat('reception_tracking_number', '|||');
        $rows = $query->groupBy('stockable_type', 'stockable_id')
            ->selectRaw("
                stockable_type,
                stockable_id,
                ROUND(SUM(remaining_weight), 2) as total_weight,
                ROUND(SUM(package_count), 2) as total_boxes,
                ROUND(SUM(total_price), 2) as total_valuation,
                COUNT(DISTINCT reception_tracking_number) as lots_count,
                {$gcLots} as lots_raw
            ")
            ->orderByDesc('total_weight')
            ->get();

        $fishList = \App\Models\FishType::with('fish_family', 'fish_process_type')->get()->keyBy('id');
        $guanoList = \App\Models\GuanoType::get()->keyBy('id');
        $pkgList = \App\Models\PackageType::get()->keyBy('id');

        $result = $rows->map(function ($row) use ($fishList, $guanoList, $pkgList) {
            $st = $row->stockable_type;
            $sid = $row->stockable_id;
            $cleanType = 'Other';
            $name = 'Article #' . $sid;
            $family = '-';
            $process = '-';

            if (str_contains($st, 'FishType')) {
                $cleanType = 'FishType';
                $fish = $fishList[$sid] ?? null;
                if ($fish) {
                    $name = $fish->name_fr ?: $fish->name ?: ('Fish #' . $sid);
                    $family = $fish->fish_family->name ?? '-';
                    $process = $fish->process ?: ($fish->fish_process_type->name ?? '-');
                }
            } elseif (str_contains($st, 'GuanoType')) {
                $cleanType = 'GuanoType';
                $guano = $guanoList[$sid] ?? null;
                if ($guano) {
                    $name = $guano->name_fr ?: ('Guano #' . $sid);
                }
            } elseif (str_contains($st, 'PackageType')) {
                $cleanType = 'PackageType';
                $pkg = $pkgList[$sid] ?? null;
                if ($pkg) {
                    $name = $pkg->name_fr ?: ('Package #' . $sid);
                }
            }

            $rawLots = explode('|||', (string) $row->lots_raw);
            $cleanLots = array_values(array_unique(array_filter(array_map(fn($r) => self::cleanLotNumber($r), $rawLots))));

            $totW = (float) $row->total_weight;
            $totVal = (float) $row->total_valuation;
            $wap = $totW > 0 ? round($totVal / $totW, 2) : 0;

            return [
                'stockable_type' => $cleanType,
                'stockable_id' => $sid,
                'article_name' => $name,
                'family_name' => $family,
                'process_name' => $process,
                'total_weight' => $totW,
                'total_boxes' => (float) $row->total_boxes,
                'total_valuation' => $totVal,
                'wap' => $wap,
                'lots_count' => (int) $row->lots_count,
                'active_lots_preview' => array_slice($cleanLots, 0, 10),
            ];
        })->values();

        return response()->json(['species' => $result]);
    }
}
