<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use BroadcastsModelChanges;

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
                        \App\Models\FishType::class => ['fish_family'],
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
}
