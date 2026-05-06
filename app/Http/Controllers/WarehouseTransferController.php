<?php

namespace App\Http\Controllers;

use App\Models\WarehouseTransfer;
use App\Models\DetailWarehouseTransfer;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseTransferController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Store a new warehouse transfer with detail lines and warehouse operations.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'transfer_tracking_number' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'truck_ids' => 'nullable|array',
            'truck_ids.*' => 'exists:trucks,id',
            'details' => 'required|array|min:1',
            'details.*.warehouse_transfer_category_id' => 'nullable|exists:warehouse_transfer_categories,id',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.source_warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.source_tracking_number' => 'nullable|string',
            'details.*.destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.truck_id' => 'nullable|exists:trucks,id',
        ]);

        return DB::transaction(function () use ($data) {
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $transfer = WarehouseTransfer::create([
                'date' => $data['date'],
                'total_weight' => $totalWeight,
                'transfer_tracking_number' => $data['transfer_tracking_number'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Attach trucks
            if (!empty($data['truck_ids'])) {
                $transfer->trucks()->sync($data['truck_ids']);
            }

            // Create detail lines + warehouse operations
            foreach ($data['details'] as $detail) {
                $dwt = $transfer->detailWarehouseTransfers()->create($detail);
                $this->processWarehouseTransfer($dwt, $transfer);
            }

            $transfer->load('detailWarehouseTransfers', 'trucks');
            $this->broadcastChange('WarehouseTransfer', 'created', $transfer->id, $transfer->toArray());
            return response()->json(['warehouse_transfer' => $transfer], 201);
        });
    }

    /**
     * Update an existing warehouse transfer.
     * Reverses old warehouse operations, deletes old detail lines,
     * then recreates everything from the new payload.
     */
    public function update(Request $request, WarehouseTransfer $warehouseTransfer)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'transfer_tracking_number' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'truck_ids' => 'nullable|array',
            'truck_ids.*' => 'exists:trucks,id',
            'details' => 'required|array|min:1',
            'details.*.warehouse_transfer_category_id' => 'nullable|exists:warehouse_transfer_categories,id',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.source_warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.source_tracking_number' => 'nullable|string',
            'details.*.destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.truck_id' => 'nullable|exists:trucks,id',
        ]);

        return DB::transaction(function () use ($data, $warehouseTransfer) {
            // 1. Reverse old warehouse operations
            $this->reverseWarehouseOperations($warehouseTransfer);

            // 2. Delete old detail lines
            $warehouseTransfer->detailWarehouseTransfers()->delete();

            // 3. Update transfer header
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;
            $warehouseTransfer->update([
                'date' => $data['date'],
                'total_weight' => $totalWeight,
                'transfer_tracking_number' => $data['transfer_tracking_number'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // 4. Sync trucks
            $warehouseTransfer->trucks()->sync($data['truck_ids'] ?? []);

            // 5. Recreate detail lines + warehouse operations
            foreach ($data['details'] as $detail) {
                $dwt = $warehouseTransfer->detailWarehouseTransfers()->create($detail);
                $this->processWarehouseTransfer($dwt, $warehouseTransfer);
            }

            $warehouseTransfer->load('detailWarehouseTransfers', 'trucks');
            $this->broadcastChange('WarehouseTransfer', 'updated', $warehouseTransfer->id, $warehouseTransfer->toArray());
            return response()->json(['warehouse_transfer' => $warehouseTransfer]);
        });
    }

    public function show(WarehouseTransfer $warehouseTransfer)
    {
        $warehouseTransfer->load(
            'detailWarehouseTransfers.warehouseTransferCategory',
            'detailWarehouseTransfers.sourceWarehouse',
            'detailWarehouseTransfers.destinationWarehouse',
            'detailWarehouseTransfers.stockable',
            'detailWarehouseTransfers.packageType',
            'detailWarehouseTransfers.truck',
            'trucks'
        );
        return response()->json(['warehouse_transfer' => $warehouseTransfer]);
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return response()->json(['message' => 'No IDs provided'], 400);

        DB::transaction(function () use ($ids) {
            $transfers = WarehouseTransfer::whereIn('id', $ids)->get();
            foreach ($transfers as $transfer) {
                $this->reverseWarehouseOperations($transfer);
                $transfer->delete();
            }
        });

        foreach ($ids as $id) {
            $this->broadcastChange('WarehouseTransfer', 'deleted', (int) $id);
        }

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    public function get_transfers(Request $request)
    {
        $query = WarehouseTransfer::with([
            'trucks',
            'detailWarehouseTransfers.warehouseTransferCategory',
            'detailWarehouseTransfers.sourceWarehouse',
            'detailWarehouseTransfers.destinationWarehouse',
            'detailWarehouseTransfers.stockable',
            'detailWarehouseTransfers.packageType',
            'detailWarehouseTransfers.truck',
        ]);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $transfers = $query->orderBy('date', 'desc')->get();
        return response()->json(['transfers' => $transfers]);
    }

    public function checkTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $exists = WarehouseTransfer::whereJsonContains('transfer_tracking_number', $data['tracking_number'])
            ->first();

        if ($exists) {
            return response()->json([
                'exists' => true,
                'transfer' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'weight' => $exists->total_weight,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Process warehouse balance for a detail warehouse transfer.
     *
     * 1. Stock OUT from source warehouse: create WarehouseBalanceOperation on matching balance
     * 2. Stock IN to destination warehouse: create new WarehouseBalance
     */
    private function processWarehouseTransfer(DetailWarehouseTransfer $dwt, WarehouseTransfer $transfer): void
    {
        if (!$dwt->stockable_type || !$dwt->stockable_id || !$dwt->source_warehouse_id || !$dwt->destination_warehouse_id) {
            return;
        }

        $weight = $dwt->weight ?? 0;
        $trackingNumber = $dwt->source_tracking_number;

        // 1. STOCK OUT from source warehouse
        $balance = WarehouseBalance::where('warehouse_id', $dwt->source_warehouse_id)
            ->where('stockable_type', $dwt->stockable_type)
            ->where('stockable_id', $dwt->stockable_id)
            ->where('warehouse_operation', 'stock_in')
            ->where('is_finished', false)
            ->when($trackingNumber, fn($q) => $q->where('reception_tracking_number', $trackingNumber))
            ->orderBy('date', 'asc')
            ->first();

        if ($balance) {
            WarehouseBalanceOperation::create([
                'warehouse_balance_id' => $balance->id,
                'date' => $transfer->date,
                'weight' => $weight,
                'unit_price' => $balance->unit_price ?? 0,
                'total_price' => ($balance->unit_price ?? 0) * $weight,
                'package_count' => $dwt->nbr_box ?? 0,
                'source_type' => 'WarehouseTransfer',
                'source_id' => $transfer->id,
                'client_name' => null,
            ]);
            $balance->recalculateRemaining();

            // 2. STOCK IN to destination warehouse
            WarehouseBalance::create([
                'warehouse_id' => $dwt->destination_warehouse_id,
                'stockable_type' => $dwt->stockable_type,
                'stockable_id' => $dwt->stockable_id,
                'reception_tracking_number' => $trackingNumber,
                'warehouse_operation' => 'stock_in',
                'date' => $transfer->date,
                'total_weight' => $weight,
                'remaining_weight' => $weight,
                'total_price' => ($balance->unit_price ?? 0) * $weight,
                'unit_price' => $balance->unit_price ?? 0,
                'package_count' => $dwt->nbr_box ?? 0,
                'package_type_id' => $dwt->package_type_id,
                'supplier_name' => $balance->supplier_name,
                'origin_name' => $balance->origin_name,
                'fishery_name' => $balance->fishery_name,
                'source_type' => 'WarehouseTransfer',
                'source_id' => $transfer->id,
                'is_finished' => false,
            ]);
        }
    }

    /**
     * Reverse all warehouse operations for a transfer.
     * Uses the parent transfer ID stored in source_id.
     */
    private function reverseWarehouseOperations(WarehouseTransfer $transfer): void
    {
        // Delete the stock_in balances at destination
        WarehouseBalance::where('source_type', 'WarehouseTransfer')
            ->where('source_id', $transfer->id)
            ->delete();

        // Delete the stock_out operations at source and recalculate
        $ops = WarehouseBalanceOperation::where('source_type', 'WarehouseTransfer')
            ->where('source_id', $transfer->id)
            ->get();

        foreach ($ops as $op) {
            $parentBalance = $op->warehouseBalance;
            $op->delete();
            if ($parentBalance) {
                $parentBalance->recalculateRemaining();
            }
        }
    }
}
