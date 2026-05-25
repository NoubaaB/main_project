<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use App\Models\DetailShipping;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    use BroadcastsModelChanges;
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'shipping_category_id' => 'required|exists:shipping_categories,id',
            'shipping_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'direction' => 'nullable|string|in:local,export',
            'client_id' => 'nullable|exists:clients,id',
            'truck_ids' => 'nullable|array',
            'truck_ids.*' => 'exists:trucks,id',
            'details' => 'required|array|min:1',
            'details.*.detail_shipping_category_id' => 'required|exists:detail_shipping_categories,id',
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
        ]);

        return DB::transaction(function () use ($data) {
            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $shipping = Shipping::create([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'shipping_category_id' => $data['shipping_category_id'],
                'shipping_tracking_number' => $data['shipping_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'description' => $data['description'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'direction' => $data['direction'] ?? null,
                'client_id' => $data['client_id'] ?? null,
            ]);

            if (!empty($data['truck_ids']))
                $shipping->trucks()->sync($data['truck_ids']);

            foreach ($data['details'] as $detail) {
                $ds = $shipping->detailShippings()->create($detail);
                $this->processWarehouseOperation($ds, $shipping);
            }

            $shipping->load('shippingCategory', 'client', 'trucks', 'origin', 'clientInvoice', 'detailShippings.detailShippingCategory', 'detailShippings.stockable', 'detailShippings.warehouse', 'detailShippings.packageType');
            $this->broadcastChange('Shipping', 'created', $shipping->id, $shipping->toArray());
            $this->broadcastChange('WarehouseBalance', 'updated', $shipping->id);
            return response()->json(['shipping' => $shipping], 201);
        });
    }

    public function show(Shipping $shipping)
    {
        $shipping->load(
            'detailShippings.detailShippingCategory',
            'detailShippings.packageType',
            'detailShippings.warehouse',
            'detailShippings.truck',
            'client',
            'trucks',
            'shippingCategory',
            'fishery',
            'ship',
            'origin'
        );
        return response()->json(['shipping' => $shipping]);
    }

    public function update(Request $request, Shipping $shipping)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'shipping_category_id' => 'required|exists:shipping_categories,id',
            'shipping_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'direction' => 'nullable|string|in:local,export',
            'client_id' => 'nullable|exists:clients,id',
            'truck_ids' => 'nullable|array',
            'details' => 'required|array|min:1',
            'details.*.detail_shipping_category_id' => 'required|exists:detail_shipping_categories,id',
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
        ]);

        // Capture old state for audit trail
        $oldData = $shipping->toArray();

        return DB::transaction(function () use ($data, $shipping, $oldData) {
            $this->reverseWarehouseOperations($shipping);
            $shipping->detailShippings()->delete();

            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $shipping->update([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'shipping_category_id' => $data['shipping_category_id'],
                'shipping_tracking_number' => $data['shipping_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'description' => $data['description'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'direction' => $data['direction'] ?? null,
                'client_id' => $data['client_id'] ?? null,
            ]);

            $shipping->trucks()->sync($data['truck_ids'] ?? []);

            foreach ($data['details'] as $detail) {
                $ds = $shipping->detailShippings()->create($detail);
                $this->processWarehouseOperation($ds, $shipping);
            }

            $shipping->load('shippingCategory', 'client', 'trucks', 'origin', 'clientInvoice', 'detailShippings.detailShippingCategory', 'detailShippings.stockable', 'detailShippings.warehouse', 'detailShippings.packageType');
            $this->broadcastChange('Shipping', 'updated', $shipping->id, $shipping->toArray(), $oldData);
            $this->broadcastChange('WarehouseBalance', 'updated', $shipping->id);
            return response()->json(['shipping' => $shipping]);
        });
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids))
            return response()->json(['message' => 'No IDs provided'], 400);

        DB::transaction(function () use ($ids) {
            $shippings = Shipping::whereIn('id', $ids)->get();
            foreach ($shippings as $shipping) {
                $this->reverseWarehouseOperations($shipping);
                $shipping->delete();
            }
        });

        foreach ($ids as $id) {
            $this->broadcastChange('Shipping', 'deleted', (int) $id);
        }
        $this->broadcastChange('WarehouseBalance', 'updated', 0);

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    public function get_shippings(Request $request)
    {
        $query = Shipping::with([
            'shippingCategory',
            'client',
            'trucks',
            'origin',
            'clientInvoice',
            'detailShippings.detailShippingCategory',
            'detailShippings.stockable',
            'detailShippings.warehouse',
            'detailShippings.packageType',
        ]);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        return response()->json(['shippings' => $query->orderBy('date', 'desc')->get()]);
    }

    public function checkShippingTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'shipping_tracking_number' => 'required|string',
            'shipping_category_id' => 'required|exists:shipping_categories,id',
        ]);

        $exists = Shipping::where('shipping_category_id', $data['shipping_category_id'])
            ->whereJsonContains('shipping_tracking_number', $data['shipping_tracking_number'])
            ->first();

        if ($exists) {
            $clientName = $exists->client?->name ?? '—';
            return response()->json([
                'exists' => true,
                'shipping' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'client' => $clientName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    private function processWarehouseOperation(DetailShipping $ds, Shipping $shipping): void
    {
        if (!$ds->warehouse_id || !$ds->warehouse_operation || !$ds->stockable_type || !$ds->stockable_id)
            return;

        $weight = $ds->weight ?? 0;
        $totalPrice = $ds->total_price ?? 0;
        $packages = $ds->nbr_box ?? 0;
        $clientName = $shipping->client?->name ?? '';

        if ($ds->warehouse_operation === 'stock_in') {
            WarehouseBalance::create([
                'warehouse_id' => $ds->warehouse_id,
                'stockable_type' => $ds->stockable_type,
                'stockable_id' => $ds->stockable_id,
                'reception_tracking_number' => null,
                'warehouse_operation' => 'stock_in',
                'date' => $shipping->date,
                'total_weight' => $weight,
                'remaining_weight' => $weight,
                'total_price' => $totalPrice,
                'unit_price' => $ds->unit_price ?? 0,
                'package_count' => $packages,
                'package_type_id' => $ds->package_type_id,
                'client_name' => $clientName ?: null,
                'origin_name' => $shipping->origin?->name,
                'source_type' => 'DetailShipping',
                'source_id' => $ds->id,
                'is_finished' => false,
            ]);
        } else {
            // stock_out: find matching balance
            $balance = WarehouseBalance::where('warehouse_id', $ds->warehouse_id)
                ->where('stockable_type', $ds->stockable_type)
                ->where('stockable_id', $ds->stockable_id)
                ->where('warehouse_operation', 'stock_in')
                ->where('is_finished', false)
                ->orderBy('date', 'asc')
                ->first();

            if ($balance) {
                WarehouseBalanceOperation::create([
                    'warehouse_balance_id' => $balance->id,
                    'date' => $shipping->date,
                    'weight' => $weight,
                    'unit_price' => $ds->unit_price ?? 0,
                    'total_price' => $totalPrice,
                    'package_count' => $packages,
                    'source_type' => 'DetailShipping',
                    'source_id' => $ds->id,
                    'client_name' => $clientName ?: null,
                ]);
                $balance->recalculateRemaining();
            }
        }
    }

    private function reverseWarehouseOperations(Shipping $shipping): void
    {
        foreach ($shipping->detailShippings as $ds) {
            if (!$ds->warehouse_id || !$ds->stockable_type || !$ds->stockable_id)
                continue;

            if ($ds->warehouse_operation === 'stock_in') {
                WarehouseBalance::where('source_type', 'DetailShipping')
                    ->where('source_id', $ds->id)
                    ->delete();
            } else {
                $ops = WarehouseBalanceOperation::where('source_type', 'DetailShipping')
                    ->where('source_id', $ds->id)
                    ->get();
                foreach ($ops as $op) {
                    $balance = $op->warehouseBalance;
                    $op->delete();
                    if ($balance)
                        $balance->recalculateRemaining();
                }
            }
        }
    }
}



