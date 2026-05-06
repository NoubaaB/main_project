<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\DetailCharge;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChargeController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * Store a new charge with detail charges and warehouse operations.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'charge_category_id' => 'required|exists:charge_categories,id',
            'reception_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'description' => 'nullable|string|max:1000',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'truck_ids' => 'nullable|array',
            'truck_ids.*' => 'exists:trucks,id',
            'details' => 'required|array|min:1',
            'details.*.detail_charge_category_id' => 'required|exists:detail_charge_categories,id',
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
            'details.*.truck_id' => 'nullable|exists:trucks,id',
            'details.*.supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        return DB::transaction(function () use ($data, $request) {
            // Calculate totals
            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            // Create charge
            $charge = Charge::create([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'charge_category_id' => $data['charge_category_id'],
                'reception_tracking_number' => $data['reception_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'description' => $data['description'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
            ]);


            // Attach trucks
            if (!empty($data['truck_ids'])) {
                $charge->trucks()->sync($data['truck_ids']);
            }

            // Create detail charges + warehouse operations
            foreach ($data['details'] as $detail) {
                $dc = $charge->detailCharges()->create($detail);
                $this->processWarehouseOperation($dc, $charge);
            }

            $charge->load('chargeCategory', 'supplier', 'trucks', 'fishery', 'ship', 'origin', 'production', 'supplierInvoice', 'detailCharges.detailChargeCategory', 'detailCharges.stockable', 'detailCharges.warehouse', 'detailCharges.packageType');
            $this->broadcastChange('Charge', 'created', $charge->id, $charge->toArray());
            $this->broadcastChange('WarehouseBalance', 'updated', $charge->id);
            return response()->json(['charge' => $charge], 201);
        });
    }

    public function show(Charge $charge)
    {
        $charge->load(
            'detailCharges.detailChargeCategory',
            'detailCharges.packageType',
            'detailCharges.warehouse',
            'detailCharges.truck',
            'supplier',
            'trucks',
            'chargeCategory',
            'fishery',
            'ship',
            'origin',
            'production',
            'supplierInvoice'
        );
        return response()->json(['charge' => $charge]);
    }

    public function update(Request $request, Charge $charge)
    {
        // Block modification if charge is linked to a supplier invoice
        if ($charge->invoice_id) {
            return response()->json([
                'message' => 'This charge is linked to a supplier invoice and cannot be modified.'
            ], 403);
        }

        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'charge_category_id' => 'required|exists:charge_categories,id',
            'reception_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'description' => 'nullable|string|max:1000',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'truck_ids' => 'nullable|array',
            'details' => 'required|array|min:1',
            'details.*.detail_charge_category_id' => 'required|exists:detail_charge_categories,id',
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
            'details.*.truck_id' => 'nullable|exists:trucks,id',
            'details.*.supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        return DB::transaction(function () use ($data, $charge) {
            // Reverse existing warehouse operations
            $this->reverseWarehouseOperations($charge);

            // Delete old detail charges
            $charge->detailCharges()->delete();

            // Recalculate totals
            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $charge->update([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'charge_category_id' => $data['charge_category_id'],
                'reception_tracking_number' => $data['reception_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'description' => $data['description'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
            ]);

            $charge->trucks()->sync($data['truck_ids'] ?? []);

            foreach ($data['details'] as $detail) {
                $dc = $charge->detailCharges()->create($detail);
                $this->processWarehouseOperation($dc, $charge);
            }

            $charge->load('chargeCategory', 'supplier', 'trucks', 'fishery', 'ship', 'origin', 'production', 'supplierInvoice', 'detailCharges.detailChargeCategory', 'detailCharges.stockable', 'detailCharges.warehouse', 'detailCharges.packageType');
            $this->broadcastChange('Charge', 'updated', $charge->id, $charge->toArray());
            $this->broadcastChange('WarehouseBalance', 'updated', $charge->id);
            return response()->json(['charge' => $charge]);
        });
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids))
            return response()->json(['message' => 'No IDs provided'], 400);

        // Filter out charges that are linked to invoices
        $invoicedCount = Charge::whereIn('id', $ids)->whereNotNull('invoice_id')->count();
        if ($invoicedCount > 0) {
            return response()->json([
                'message' => 'Some charges are linked to supplier invoices and cannot be deleted.',
                'invoiced_count' => $invoicedCount,
            ], 403);
        }

        DB::transaction(function () use ($ids) {
            $charges = Charge::whereIn('id', $ids)->get();
            foreach ($charges as $charge) {
                $this->reverseWarehouseOperations($charge);
                $charge->delete();
            }
        });

        foreach ($ids as $id) {
            $this->broadcastChange('Charge', 'deleted', (int) $id);
        }
        $this->broadcastChange('WarehouseBalance', 'updated', 0);

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    public function get_charges(Request $request)
    {
        $query = Charge::with([
            'chargeCategory',
            'supplier',
            'trucks',
            'fishery',
            'ship',
            'origin',
            'production',
            'supplierInvoice',
            'detailCharges.detailChargeCategory',
            'detailCharges.stockable',
            'detailCharges.warehouse',
            'detailCharges.packageType',
        ]);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $charges = $query->orderBy('date', 'desc')->get();
        return response()->json(['charges' => $charges]);
    }

    /**
     * Get charges with is_production=true that have NO production linked yet.
     * Used by the Charge-Production Management page.
     */
    public function get_production_charges(Request $request)
    {
        $charges = Charge::with([
            'chargeCategory',
            'supplier',
            'trucks',
            'fishery',
            'ship',
            'origin',
            'detailCharges.detailChargeCategory',
            'detailCharges.stockable',
            'detailCharges.warehouse',
            'detailCharges.packageType',
        ])
            ->whereHas('chargeCategory', function ($q) {
                $q->where('is_production', true);
            })
            ->whereDoesntHave('production')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['charges' => $charges]);
    }

    public function checkTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
            'charge_category_id' => 'required|exists:charge_categories,id',
        ]);

        $exists = Charge::where('charge_category_id', $data['charge_category_id'])
            ->whereJsonContains('reception_tracking_number', $data['tracking_number'])
            ->first();

        if ($exists) {
            $supplierName = $exists->supplier?->name ?? '—';
            return response()->json([
                'exists' => true,
                'charge' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'provider' => $supplierName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function checkOnpTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
            'charge_category_id' => 'required|exists:charge_categories,id',
        ]);

        $exists = Charge::where('charge_category_id', $data['charge_category_id'])
            ->whereJsonContains('tracking_onp_numbers', $data['tracking_number'])
            ->first();

        if ($exists) {
            $supplierName = $exists->supplier?->name ?? '—';
            return response()->json([
                'exists' => true,
                'charge' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'provider' => $supplierName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Process warehouse balance for a detail charge.
     *
     * stock_in  → creates a WarehouseBalance row
     * stock_out → finds matching balance by tracking number and creates a WarehouseBalanceOperation
     */
    private function processWarehouseOperation(DetailCharge $dc, Charge $charge): void
    {
        if (!$dc->warehouse_id || !$dc->warehouse_operation || !$dc->stockable_type || !$dc->stockable_id) {
            return;
        }

        $weight = $dc->weight ?? 0;
        $totalPrice = $dc->total_price ?? 0;
        $packages = $dc->nbr_box ?? 0;
        $supplierName = $charge->supplier?->name ?? '';
        $trackingNumber = $charge->reception_tracking_number[0] ?? null;

        if ($dc->warehouse_operation === 'stock_in') {
            // Create a new warehouse balance entry
            WarehouseBalance::create([
                'warehouse_id' => $dc->warehouse_id,
                'stockable_type' => $dc->stockable_type,
                'stockable_id' => $dc->stockable_id,
                'reception_tracking_number' => $trackingNumber,
                'warehouse_operation' => 'stock_in',
                'date' => $charge->date,
                'total_weight' => $weight,
                'remaining_weight' => $weight,
                'total_price' => $totalPrice,
                'unit_price' => $dc->unit_price ?? 0,
                'package_count' => $packages,
                'package_type_id' => $dc->package_type_id,
                'supplier_name' => $supplierName ?: null,
                'origin_name' => $charge->origin?->name,
                'fishery_name' => $charge->fishery?->name,
                'source_type' => 'DetailCharge',
                'source_id' => $dc->id,
                'is_finished' => false,
            ]);
        } else {
            // stock_out: find matching balance and create operation
            $balance = WarehouseBalance::where('warehouse_id', $dc->warehouse_id)
                ->where('stockable_type', $dc->stockable_type)
                ->where('stockable_id', $dc->stockable_id)
                ->where('warehouse_operation', 'stock_in')
                ->where('is_finished', false)
                ->when($trackingNumber, fn($q) => $q->where('reception_tracking_number', $trackingNumber))
                ->orderBy('date', 'asc')
                ->first();

            if ($balance) {
                WarehouseBalanceOperation::create([
                    'warehouse_balance_id' => $balance->id,
                    'date' => $charge->date,
                    'weight' => $weight,
                    'unit_price' => $dc->unit_price ?? 0,
                    'total_price' => $totalPrice,
                    'package_count' => $packages,
                    'source_type' => 'DetailCharge',
                    'source_id' => $dc->id,
                    'client_name' => null,
                ]);
                $balance->recalculateRemaining();
            }
        }
    }

    /**
     * Reverse all warehouse operations for a charge's detail charges.
     */
    private function reverseWarehouseOperations(Charge $charge): void
    {
        foreach ($charge->detailCharges as $dc) {
            if (!$dc->warehouse_id || !$dc->stockable_type || !$dc->stockable_id)
                continue;

            if ($dc->warehouse_operation === 'stock_in') {
                // Delete the balance record (cascade deletes operations)
                WarehouseBalance::where('source_type', 'DetailCharge')
                    ->where('source_id', $dc->id)
                    ->delete();
            } else {
                // Delete the operation and recalculate parent balance
                $ops = WarehouseBalanceOperation::where('source_type', 'DetailCharge')
                    ->where('source_id', $dc->id)
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
