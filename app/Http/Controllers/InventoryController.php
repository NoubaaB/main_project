<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\DetailInventory;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
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
}
