<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Charge;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductionController extends Controller
{
    private function passes($stockable_type, $stockable_id)
    {
        $modelClass = 'App\\Models\\' . Str::studly($stockable_type);
        if (!class_exists($modelClass)) return false;
        return $modelClass::find($stockable_id);
    }

    /**
     * List productions within a date range.
     */
    public function index(Request $request): JsonResponse
    {
        $date_start = $request->query('date_start');
        $date_end   = $request->query('date_end');

        $productions = Production::whereBetween('date', [$date_start, $date_end])
            ->with(['suppliers', 'charge', 'production_category', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations'])
            ->orderBy('date')
            ->get();

        return response()->json(['productions' => $productions], 200);
    }

    /**
     * Store a new production linked to a charge.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'charge_id' => 'sometimes|nullable|exists:charges,id|unique:productions,charge_id',
            'production_category_id' => 'required|exists:production_categories,id',
            'date' => 'required|date',
            'reception_tracking_number' => 'sometimes|array',
            'reception_tracking_number.*' => 'string|max:50',
            'tracking_onp_numbers' => 'sometimes|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'production_tracking_numbers' => 'sometimes|array',
            'production_tracking_numbers.*' => 'string|max:50',
            'note' => 'sometimes|string|max:500|nullable',
            'fishery_id' => 'sometimes|nullable|exists:fisheries,id',
            'origin_id' => 'sometimes|nullable|exists:origins,id',
            'truck_ids' => 'sometimes|array',
            'supplier_ids' => 'sometimes|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'warehouse_category_operations' => 'sometimes|array',
            'warehouse_category_operations.*' => 'array',
            'warehouse_category_operations.*.id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.charge_details' => 'sometimes|array',
            'warehouse_category_operations.*.charge_details.*' => 'array',
            'warehouse_category_operations.*.charge_details.*.warehouse_category_operation_id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.charge_details.*.truck_ids' => 'sometimes|array|exists:trucks,id',
            'warehouse_category_operations.*.charge_details.*.total_weight' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_weight_bill' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_price' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_package_count' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.unit_price' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.stockable_id' => 'sometimes|nullable',
            'warehouse_category_operations.*.charge_details.*.stockable_type' => 'sometimes|nullable|string',
            'warehouse_category_operations.*.charge_details.*.description' => 'sometimes|nullable|string|max:500',
            'warehouse_category_operations.*.charge_details.*.warehouse_id' => 'sometimes|nullable|exists:warehouses,id',
            'warehouse_category_operations.*.charge_details.*.package_type_id' => 'sometimes|nullable|exists:package_types,id'
        ]);

        // 1. Create production record
        $production = Production::create([
            'charge_id' => $data['charge_id'],
            'production_category_id' => $data['production_category_id'],
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'] ?? null,
            'reception_tracking_number' => $data['reception_tracking_number'] ?? [],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? [],
            'production_tracking_numbers' => $data['production_tracking_numbers'] ?? [],
            'fishery_id' => $data['fishery_id'] ?? null,
            'origin_id' => $data['origin_id'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        if (isset($data['truck_ids'])) {
            $production->trucks()->attach($data['truck_ids']);
        }

        // 2. Create warehouse operations (same pattern as ChargeController)
        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $wco) {
                if (isset($wco['charge_details'])) {
                    foreach ($wco['charge_details'] as $detail) {
                        $truck_ids = [];
                        if (isset($detail['truck_ids'])) {
                            $truck_ids = $detail['truck_ids'];
                            unset($detail['truck_ids']);
                        }
                        if (!isset($detail['warehouse_id']) || $detail['warehouse_id'] == null) {
                            unset($detail['warehouse_id']);
                            $non_wso = $production->non_warehouse_stockable_operations()->create($detail);
                            if (count($truck_ids) > 0) {
                                $non_wso->trucks()->attach($truck_ids);
                            }
                        } else {
                            $warehouse = \App\Models\Warehouse::find($detail['warehouse_id']);
                            $stockable = $this->passes($detail['stockable_type'], $detail['stockable_id']);
                            $ws = $warehouse->warehouse_stockables()->firstOrCreate([
                                'stockable_type' => $detail['stockable_type'],
                                'stockable_id' => $stockable->id,
                            ]);

                            $wstn = $ws->warehouse_stockable_tracking_numbers()->create([
                                'tracking_numbers' => $data['reception_tracking_number'] ?? [],
                                'unit_price' => 0.00,
                                'total_weight' => 0.00,
                                'total_price' => 0.00,
                                'total_package_count' => 0.00,
                                'warehouse_stockable_id' => $ws->id,
                            ]);

                            $op = $wstn->warehouse_stockable_tracking_number_operations()->create([
                                'type_operation' => 'in',
                                'date' => $production->date,
                                'unit_price' => $detail['unit_price'] ?? 0,
                                'total_weight' => $detail['total_weight'],
                                'total_price' => $detail['total_price'],
                                'stockable_type' => $detail['stockable_type'],
                                'stockable_id' => $stockable->id,
                                'total_package_count' => $detail['total_package_count'],
                                'package_type_id' => $detail['package_type_id'] ?? null,
                                'warehouse_category_operation_id' => $detail['warehouse_category_operation_id'],
                            ]);
                            $production->warehouse_stockable_tracking_number_operations()->save($op);
                        }
                        $production->calc();
                    }
                }
            }
        }

        if (isset($data['supplier_ids'])) {
            $production->suppliers()->sync($data['supplier_ids']);
        }

        return response()->json([
            'message' => 'Production created successfully',
            'data' => $production->load(['suppliers', 'charge', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    /**
     * Show a single production.
     */
    public function show(Production $production): JsonResponse
    {
        $production->load(['suppliers', 'trucks', 'charge', 'production_category', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations', 'fishery', 'origin']);
        return response()->json(['production' => $production], 200);
    }

    /**
     * Update production.
     */
    public function update(Request $request, Production $production): JsonResponse
    {
        $data = $request->validate([
            'production_category_id' => 'required|exists:production_categories,id',
            'date' => 'required|date',
            'reception_tracking_number' => 'sometimes|array',
            'reception_tracking_number.*' => 'string|max:50',
            'tracking_onp_numbers' => 'sometimes|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'production_tracking_numbers' => 'sometimes|array',
            'production_tracking_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'fishery_id' => 'sometimes|exists:fisheries,id',
            'origin_id' => 'sometimes|exists:origins,id',
            'truck_ids' => 'sometimes|array|exists:trucks,id',
            'supplier_ids' => 'sometimes|array',
            'supplier_ids.*' => 'exists:suppliers,id',
        ]);

        $production->update([
            'production_category_id' => $data['production_category_id'],
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'] ?? null,
            'reception_tracking_number' => $data['reception_tracking_number'] ?? [],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? [],
            'production_tracking_numbers' => $data['production_tracking_numbers'] ?? [],
            'fishery_id' => $data['fishery_id'] ?? null,
            'origin_id' => $data['origin_id'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        if (isset($data['truck_ids'])) {
            $production->trucks()->sync($data['truck_ids']);
        }

        if (isset($data['supplier_ids'])) {
            $production->suppliers()->sync($data['supplier_ids']);
        }

        return response()->json([
            'message' => 'Production updated successfully',
            'data' => $production->load(['suppliers', 'charge', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    /**
     * Delete production(s).
     */
    public function destroy(Request $request): JsonResponse
    {
        $ids = $request->query('ids');
        $status = Production::whereIn('id', $ids ?? [])->delete();
        return response()->json(['message' => $status ? 'Deleted successfully' : 'No records deleted'], 200);
    }
    /**
     * Check if a production_tracking_number already exists in productions.
     */
    public function checkProductionTrackingNumberUniqueness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'production_tracking_number' => 'required|string|max:50',
            'exclude_production_id' => 'sometimes|nullable|integer',
        ]);

        $query = Production::whereJsonContains('production_tracking_numbers', $data['production_tracking_number']);

        if (!empty($data['exclude_production_id'])) {
            $query->where('id', '!=', $data['exclude_production_id']);
        }

        $existing = $query->with('suppliers')->first();

        if ($existing) {
            return response()->json([
                'unique' => false,
                'production' => [
                    'id' => $existing->id,
                    'production_tracking_numbers' => $existing->production_tracking_numbers,
                    'date' => $existing->date?->format('Y-m-d'),
                    'suppliers' => $existing->suppliers->pluck('name')->toArray(),
                    'total_weight' => $existing->total_weight,
                ],
            ], 200);
        }

        return response()->json(['unique' => true], 200);
    }
}
