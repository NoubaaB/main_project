<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\DetailInventory;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Models\WarehouseStockReset;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Store a new inventory with detail inventories and warehouse operations.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'inventory_tracking_number' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.detail_inventory_category_id' => 'nullable|exists:detail_inventory_categories,id',
            'details.*.article_description' => 'nullable|string',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.unit_price' => 'nullable|numeric|min:0',
            'details.*.total_price' => 'nullable|numeric|min:0',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $inventory = Inventory::create([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'inventory_tracking_number' => $data['inventory_tracking_number'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['details'] as $detail) {
                $di = $inventory->detailInventories()->create($detail);
                $this->processWarehouseOperation($di, $inventory);
            }

            $inventory->load('detailInventories');
            $this->broadcastChange('Inventory', 'created', $inventory->id, $inventory->toArray());
            return response()->json(['inventory' => $inventory], 201);
        });
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(
            'detailInventories.detailInventoryCategory',
            'detailInventories.packageType',
            'detailInventories.warehouse',
            'detailInventories.stockable'
        );
        return response()->json(['inventory' => $inventory]);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'inventory_tracking_number' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.detail_inventory_category_id' => 'nullable|exists:detail_inventory_categories,id',
            'details.*.article_description' => 'nullable|string',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.unit_price' => 'nullable|numeric|min:0',
            'details.*.total_price' => 'nullable|numeric|min:0',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
        ]);

        return DB::transaction(function () use ($data, $inventory) {
            // Reverse existing warehouse operations
            $this->reverseWarehouseOperations($inventory);

            // Delete old detail inventories
            $inventory->detailInventories()->delete();

            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $inventory->update([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'inventory_tracking_number' => $data['inventory_tracking_number'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['details'] as $detail) {
                $di = $inventory->detailInventories()->create($detail);
                $this->processWarehouseOperation($di, $inventory);
            }

            $inventory->load('detailInventories');
            $this->broadcastChange('Inventory', 'updated', $inventory->id, $inventory->toArray());
            return response()->json(['inventory' => $inventory]);
        });
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return response()->json(['message' => 'No IDs provided'], 400);

        DB::transaction(function () use ($ids) {
            $inventories = Inventory::whereIn('id', $ids)->get();
            foreach ($inventories as $inventory) {
                $this->reverseWarehouseOperations($inventory);
                $inventory->delete();
            }
        });

        foreach ($ids as $id) {
            $this->broadcastChange('Inventory', 'deleted', (int) $id);
        }

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    public function get_inventories(Request $request)
    {
        $query = Inventory::with([
            'detailInventories.detailInventoryCategory',
            'detailInventories.stockable',
            'detailInventories.warehouse',
            'detailInventories.packageType',
        ]);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->has('per_page') || $request->has('page')) {
            $perPage = (int) $request->input('per_page', 50);
            return response()->json(['inventories' => $query->orderBy('date', 'desc')->paginate($perPage)->items()]);
        } elseif (!$request->has('all')) {
            $query->where('date', '>=', now()->startOfYear()->toDateString());
        }

        $inventories = $query->orderBy('date', 'desc')->get();
        return response()->json(['inventories' => $inventories]);
    }

    /**
     * Check if an inventory tracking number already exists.
     */
    public function checkTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $exists = Inventory::whereJsonContains('inventory_tracking_numbers', $data['tracking_number'])
            ->first();

        if ($exists) {
            return response()->json([
                'exists' => true,
                'inventory' => [
                    'date' => $exists->date instanceof \Carbon\Carbon ? $exists->date->format('Y-m-d') : (string) $exists->date,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Process warehouse balance for a detail inventory.
     */
    private function processWarehouseOperation(DetailInventory $di, Inventory $inventory): void
    {
        if (!$di->warehouse_id || !$di->warehouse_operation || !$di->stockable_type || !$di->stockable_id) {
            return;
        }

        $weight = $di->weight ?? 0;
        $totalPrice = $di->total_price ?? 0;
        $packages = $di->nbr_box ?? 0;
        $trackingNumber = $inventory->inventory_tracking_numbers[0] ?? null;

        if ($di->warehouse_operation === 'stock_in') {
            WarehouseBalance::create([
                'warehouse_id' => $di->warehouse_id,
                'stockable_type' => $di->stockable_type,
                'stockable_id' => $di->stockable_id,
                'reception_tracking_number' => $trackingNumber,
                'warehouse_operation' => 'stock_in',
                'date' => $inventory->date,
                'total_weight' => $weight,
                'remaining_weight' => $weight,
                'total_price' => $totalPrice,
                'unit_price' => $di->unit_price ?? 0,
                'package_count' => $packages,
                'package_type_id' => $di->package_type_id,
                'supplier_name' => null,
                'origin_name' => null,
                'fishery_name' => null,
                'source_type' => 'DetailInventory',
                'source_id' => $di->id,
                'is_finished' => false,
            ]);
        } else {
            // stock_out
            $balance = WarehouseBalance::where('warehouse_id', $di->warehouse_id)
                ->where('stockable_type', $di->stockable_type)
                ->where('stockable_id', $di->stockable_id)
                ->where('warehouse_operation', 'stock_in')
                ->where('is_finished', false)
                ->when($trackingNumber, fn($q) => $q->where('reception_tracking_number', $trackingNumber))
                ->orderBy('date', 'asc')
                ->first();

            if ($balance) {
                WarehouseBalanceOperation::create([
                    'warehouse_balance_id' => $balance->id,
                    'date' => $inventory->date,
                    'weight' => $weight,
                    'unit_price' => $di->unit_price ?? 0,
                    'total_price' => $totalPrice,
                    'package_count' => $packages,
                    'source_type' => 'DetailInventory',
                    'source_id' => $di->id,
                    'client_name' => null,
                ]);
                $balance->recalculateRemaining();
            }
        }
    }

    /**
     * Reverse all warehouse operations for an inventory's detail inventories.
     */
    private function reverseWarehouseOperations(Inventory $inventory): void
    {
        foreach ($inventory->detailInventories as $di) {
            if (!$di->warehouse_id || !$di->stockable_type || !$di->stockable_id) continue;

            if ($di->warehouse_operation === 'stock_in') {
                WarehouseBalance::where('source_type', 'DetailInventory')
                    ->where('source_id', $di->id)
                    ->delete();
            } else {
                $ops = WarehouseBalanceOperation::where('source_type', 'DetailInventory')
                    ->where('source_id', $di->id)
                    ->get();

                foreach ($ops as $op) {
                    $balance = $op->warehouseBalance;
                    $op->delete();
                    if ($balance) {
                        $balance->recalculateRemaining();
                    }
                }
            }
        }
    }

    /**
     * Preview which articles and balances will be zeroed out for given warehouses and date.
     */
    public function previewReset(Request $request)
    {
        $warehouseIds = (array) $request->input('warehouse_ids', []);
        $date = $request->input('date', now()->toDateString());

        if (empty($warehouseIds)) {
            return response()->json([
                'warehouses' => [],
                'grand_total_weight' => 0,
                'grand_total_price' => 0,
                'grand_articles_count' => 0,
            ]);
        }

        $warehouses = Warehouse::whereIn('id', $warehouseIds)->get();
        $previewData = [];
        $grandTotalWeight = 0;
        $grandTotalPrice = 0;
        $grandArticlesCount = 0;

        foreach ($warehouses as $wh) {
            $balances = WarehouseBalance::with(['stockable', 'packageType'])
                ->where('warehouse_id', $wh->id)
                ->where('warehouse_operation', 'stock_in')
                ->where('date', '<=', $date)
                ->where('is_finished', false)
                ->where(function ($q) {
                    $q->where('remaining_weight', '>', 0)
                      ->orWhere('package_count', '>', 0);
                })
                ->get();

            $whWeight = (float) $balances->sum('remaining_weight');
            $whPrice = (float) $balances->sum(fn($b) => (float) $b->effective_total_price);
            $distinctArticles = $balances->unique(fn($b) => $b->stockable_type . '_' . $b->stockable_id);

            $articlesList = $distinctArticles->map(function ($b) use ($balances) {
                $group = $balances->where('stockable_type', $b->stockable_type)->where('stockable_id', $b->stockable_id);
                return [
                    'stockable_type' => $b->clean_stockable_type,
                    'stockable_id' => $b->stockable_id,
                    'article_name' => $b->article_name,
                    'remaining_weight' => round($group->sum('remaining_weight'), 2),
                    'package_count' => round($group->sum('package_count'), 2),
                    'total_price' => round($group->sum(fn($item) => (float) $item->effective_total_price), 2),
                    'balance_count' => $group->count(),
                ];
            })->values();

            $grandTotalWeight += $whWeight;
            $grandTotalPrice += $whPrice;
            $grandArticlesCount += $distinctArticles->count();

            $previewData[] = [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'total_weight' => round($whWeight, 2),
                'total_price' => round($whPrice, 2),
                'articles_count' => $distinctArticles->count(),
                'balances_count' => $balances->count(),
                'articles' => $articlesList,
            ];
        }

        return response()->json([
            'warehouses' => $previewData,
            'grand_total_weight' => round($grandTotalWeight, 2),
            'grand_total_price' => round($grandTotalPrice, 2),
            'grand_articles_count' => $grandArticlesCount,
        ]);
    }

    /**
     * Reset / initialize all articles in selected warehouses to 0 at the given date.
     */
    public function resetWarehouseStock(Request $request)
    {
        $data = $request->validate([
            'warehouse_ids' => 'required|array|min:1',
            'warehouse_ids.*' => 'exists:warehouses,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($data) {
            $createdResets = [];
            $totalClearedWeightAll = 0;
            $totalClearedPriceAll = 0;
            $totalBalancesAffectedAll = 0;

            foreach ($data['warehouse_ids'] as $whId) {
                $balances = WarehouseBalance::where('warehouse_id', $whId)
                    ->where('warehouse_operation', 'stock_in')
                    ->where('date', '<=', $data['date'])
                    ->where('is_finished', false)
                    ->where(function ($q) {
                        $q->where('remaining_weight', '>', 0)
                          ->orWhere('package_count', '>', 0);
                    })
                    ->get();

                $totalWeight = 0;
                $totalPrice = 0;
                $distinctArticles = [];

                $resetRecord = WarehouseStockReset::create([
                    'warehouse_id' => $whId,
                    'reset_date' => $data['date'],
                    'reason' => $data['reason'] ?? null,
                    'created_by' => auth()->id(),
                    'total_weight_cleared' => 0,
                    'total_price_cleared' => 0,
                    'articles_count' => 0,
                    'balances_affected_count' => 0,
                ]);

                foreach ($balances as $balance) {
                    $clearedWeight = (float) $balance->remaining_weight;
                    $clearedPrice = (float) ($balance->effective_total_price ?: round(($balance->unit_price ?? 0) * $clearedWeight, 2));
                    $clearedPkgs = (float) ($balance->package_count ?? 0);

                    WarehouseBalanceOperation::create([
                        'warehouse_balance_id' => $balance->id,
                        'date' => $data['date'],
                        'weight' => $clearedWeight,
                        'package_count' => $clearedPkgs,
                        'unit_price' => $balance->unit_price ?? 0,
                        'total_price' => $clearedPrice,
                        'source_type' => 'WarehouseStockReset',
                        'source_id' => $resetRecord->id,
                        'client_name' => 'Stock Reset Checkpoint',
                    ]);

                    $balance->recalculateRemaining();

                    $totalWeight += $clearedWeight;
                    $totalPrice += $clearedPrice;
                    $distinctArticles[$balance->stockable_type . '_' . $balance->stockable_id] = true;
                }

                $resetRecord->update([
                    'total_weight_cleared' => round($totalWeight, 2),
                    'total_price_cleared' => round($totalPrice, 2),
                    'articles_count' => count($distinctArticles),
                    'balances_affected_count' => $balances->count(),
                ]);

                $warehouse = Warehouse::find($whId);
                if ($warehouse) {
                    $warehouse->recalculateTotalWeight();
                }

                $resetRecord->load(['warehouse', 'creator']);
                $createdResets[] = $resetRecord;

                $totalClearedWeightAll += $totalWeight;
                $totalClearedPriceAll += $totalPrice;
                $totalBalancesAffectedAll += $balances->count();

                $this->broadcastChange('WarehouseStockReset', 'created', $resetRecord->id, $resetRecord->toArray());
                $this->broadcastChange('WarehouseBalance', 'updated', 0);
                $this->broadcastChange('Warehouse', 'updated', $whId);
            }

            return response()->json([
                'success' => true,
                'resets' => $createdResets,
                'total_weight_cleared' => round($totalClearedWeightAll, 2),
                'total_price_cleared' => round($totalClearedPriceAll, 2),
                'balances_affected' => $totalBalancesAffectedAll,
                'warehouses_count' => count($data['warehouse_ids']),
            ], 201);
        });
    }

    /**
     * Get all stock reset history checkpoints.
     */
    public function getStockResets(Request $request)
    {
        $resets = WarehouseStockReset::with(['warehouse', 'creator'])
            ->orderBy('reset_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['resets' => $resets]);
    }

    /**
     * Undo / revert a stock reset checkpoint, restoring balances and warehouse stock.
     */
    public function undoStockReset($id)
    {
        return DB::transaction(function () use ($id) {
            $reset = WarehouseStockReset::findOrFail($id);
            $whId = $reset->warehouse_id;

            $ops = WarehouseBalanceOperation::where('source_type', 'WarehouseStockReset')
                ->where('source_id', $reset->id)
                ->get();

            foreach ($ops as $op) {
                $balance = $op->warehouseBalance;
                $op->delete();
                if ($balance) {
                    $balance->recalculateRemaining();
                }
            }

            $reset->delete();

            $warehouse = Warehouse::find($whId);
            if ($warehouse) {
                $warehouse->recalculateTotalWeight();
            }

            $this->broadcastChange('WarehouseStockReset', 'deleted', (int) $id);
            $this->broadcastChange('WarehouseBalance', 'updated', 0);
            $this->broadcastChange('Warehouse', 'updated', $whId);

            return response()->json(['success' => true, 'message' => 'Stock reset successfully reversed']);
        });
    }
}

