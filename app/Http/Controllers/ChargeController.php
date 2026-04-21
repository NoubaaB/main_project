<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\ChargeAuditLog;
use App\Models\FishType;
use App\Models\GuanoType;
use App\Models\PackageType;
use App\Models\SupplierInvoice;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChargeController extends Controller
{
    //
    public function passes($stockable_type, $stockable_id)
    {

        $modelClass = 'App\\Models\\' . Str::studly($stockable_type);
        if (!class_exists($modelClass)) {
            return false;
        }

        return $modelClass::find($stockable_id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Charge $charge)
    {
        //
        return response()->json(["charge" => $charge->load(['suppliers', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations'])], 200);
    }

    public function charge(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'reception_tracking_number' => 'required|array',
            'reception_tracking_number.*' => 'string|max:50',
            'tracking_onp_numbers' => 'sometimes|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'production_tracking_numbers' => 'sometimes|array',
            'production_tracking_numbers.*' => 'string|max:50',
            'note' => 'sometimes|string|max:500|nullable',
            'fishery_id' => 'sometimes|exists:fisheries,id',
            'origin_id' => 'sometimes|exists:origins,id',
            'truck_ids' => 'sometimes|array|exists:trucks,id',
            'charge_category_id' => 'required|exists:charge_categories,id',
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

        // ✅ 1. Create parent record
        $charge = Charge::create([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'],
            'reception_tracking_number' => $data['reception_tracking_number'],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? [],
            'production_tracking_numbers' => $data['production_tracking_numbers'] ?? [],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_category_id' => $data['charge_category_id'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Audit log: created
        ChargeAuditLog::create([
            'charge_id' => $charge->id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'created',
            'changes' => [
                'date' => ['old' => null, 'new' => $charge->date?->format('Y-m-d')],
                'reception_tracking_number' => ['old' => null, 'new' => $charge->reception_tracking_number],
                'tracking_onp_numbers' => ['old' => null, 'new' => $charge->tracking_onp_numbers],
                'production_tracking_numbers' => ['old' => null, 'new' => $charge->production_tracking_numbers],
                'note' => ['old' => null, 'new' => $charge->note],
            ],
        ]);
        if (isset($data["truck_ids"])) {
            $charge->trucks()->attach($data['truck_ids']);
        }

        // ✅ 2. Create warehouse_category_operations children (details)
        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $warehouse_category_operation) {
                if (isset($warehouse_category_operation["charge_details"])) {
                    foreach ($warehouse_category_operation["charge_details"] as $charge_detail) {
                        $truck_ids = [];
                        if (isset($charge_detail["truck_ids"])) {
                            $truck_ids = $charge_detail["truck_ids"];
                            unset($charge_detail["truck_ids"]);
                        }
                        if (!isset($charge_detail["warehouse_id"]) || $charge_detail["warehouse_id"] == null) {
                            unset($charge_detail["warehouse_id"]);
                            $non_warehouse_stockable_operation = $charge->non_warehouse_stockable_operations()->create($charge_detail);
                            if (count($truck_ids) > 0) {
                                $non_warehouse_stockable_operation->trucks()->attach($truck_ids);
                            }
                        } else {
                            $warehouse = Warehouse::find($charge_detail["warehouse_id"]);
                            $warehouse_stockable = $warehouse->warehouse_stockables()->whereHasMorph('stockable', [FishType::class, GuanoType::class, PackageType::class], fn($query) => $query->where('stockable_id', $charge_detail["stockable_id"]))
                                ->first();

                            if (isset($warehouse_stockable) == false) {
                                $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                                    "unit_price" => 0.00,
                                    "total_weight" => 0.00,
                                    "total_price" => 0.00,
                                    "total_package_count" => 0.00,
                                    "warehouse_id" => $warehouse->id,
                                    "stockable_id" => $charge_detail["stockable_id"],
                                    "stockable_type" => $charge_detail["stockable_type"]
                                ]);
                            }

                            $warehouse_stockable_tracking_number = null;
                            if ($warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $data["reception_tracking_number"])->count() != 1) {
                                $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $data["reception_tracking_number"]);
                            }
                            $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                                "tracking_numbers" => $data["reception_tracking_number"],
                                "unit_price" => 0.00,
                                "total_weight" => 0.00,
                                "total_price" => 0.00,
                                "total_package_count" => 0.00,
                                "warehouse_stockable_id" => $warehouse_stockable->id,
                            ]);

                            $stockable = $this->passes($charge_detail['stockable_type'], $charge_detail['stockable_id']);
                            $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                                "type_operation" => "in",
                                "date" => $charge->date,
                                "unit_price" => isset($charge_detail['unit_price']) ? $charge_detail['unit_price'] : 0,
                                "total_weight" => $charge_detail['total_weight'],
                                "total_price" => $charge_detail['total_price'],
                                "stockable_type" => $charge_detail["stockable_type"],
                                "stockable_id" => $stockable->id,
                                "total_package_count" => $charge_detail['total_package_count'],
                                "package_type_id" => $charge_detail['package_type_id'] ?? null,
                                "warehouse_category_operation_id" => $charge_detail['warehouse_category_operation_id'],
                            ]);
                            $charge->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                        }
                        $charge->calc();
                    }
                }
            }
        }

        $charge->suppliers()->sync($data['supplier_ids']);

        return response()->json([
            'message' => 'Fresh Fish Receive created successfully',
            'data' => $charge->load(['suppliers', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    /**
     * Update the specified charge.
     */
    public function update(Request $request, Charge $charge)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'reception_tracking_number' => 'required|array',
            'reception_tracking_number.*' => 'string|max:50',
            'tracking_onp_numbers' => 'required|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'production_tracking_numbers' => 'sometimes|array',
            'production_tracking_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'fishery_id' => 'required|exists:fisheries,id',
            'origin_id' => 'required|exists:origins,id',
            'truck_ids' => 'sometimes|array|exists:trucks,id',
            'charge_category_id' => 'required|exists:charge_categories,id',
            'supplier_ids' => 'required|array',
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


        // Capture old values for audit log
        $oldValues = [
            'date' => $charge->date?->format('Y-m-d'),
            'reception_tracking_number' => $charge->reception_tracking_number,
            'tracking_onp_numbers' => $charge->tracking_onp_numbers,
            'production_tracking_numbers' => $charge->production_tracking_numbers,
            'note' => $charge->note,
            'fishery_id' => $charge->fishery_id,
            'origin_id' => $charge->origin_id,
            'charge_category_id' => $charge->charge_category_id,
        ];

        // ✅ 1. Update parent record
        $charge->update([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'],
            'reception_tracking_number' => $data['reception_tracking_number'],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'],
            'production_tracking_numbers' => $data['production_tracking_numbers'] ?? [],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_category_id' => $data['charge_category_id'],
            'updated_by' => Auth::id(),
        ]);

        // Audit log: updated
        $changes = [];
        $newValues = [
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'reception_tracking_number' => $data['reception_tracking_number'],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'],
            'note' => $data['note'],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_category_id' => $data['charge_category_id'],
        ];
        foreach ($newValues as $key => $newVal) {
            if ($oldValues[$key] != $newVal) {
                $changes[$key] = ['old' => $oldValues[$key], 'new' => $newVal];
            }
        }
        if (!empty($changes)) {
            ChargeAuditLog::create([
                'charge_id' => $charge->id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'updated',
                'changes' => $changes,
            ]);
        }

        // ✅ 2. Sync trucks
        if (isset($data['truck_ids'])) {
            $charge->trucks()->sync($data['truck_ids']);
        } else {
            $charge->trucks()->detach();
        }

        // ✅ 3. Sync non_warehouse_stockable_operations & warehouse_stockable_tracking_number_operations
        // Collect all charge_detail IDs from the payload
        $payloadNonWarehouseIds = [];
        $payloadWarehouseIds = [];

        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $warehouse_category_operation) {
                if (isset($warehouse_category_operation["charge_details"])) {
                    foreach ($warehouse_category_operation["charge_details"] as $charge_detail) {
                        if (!isset($charge_detail["warehouse_id"]) || $charge_detail["warehouse_id"] == null) {
                            // Non-warehouse operation
                            if (isset($charge_detail["id"]) && is_numeric($charge_detail["id"])) {
                                $payloadNonWarehouseIds[] = $charge_detail["id"];
                            }
                        } else {
                            // Warehouse operation
                            if (isset($charge_detail["id"]) && is_numeric($charge_detail["id"])) {
                                $payloadWarehouseIds[] = $charge_detail["id"];
                            }
                        }
                    }
                }
            }
        }

        // Delete non_warehouse_stockable_operations that are NOT in the payload (removed by user)
        $existingNonWarehouseOps = $charge->non_warehouse_stockable_operations;
        foreach ($existingNonWarehouseOps as $op) {
            if (!in_array($op->id, $payloadNonWarehouseIds)) {
                $op->trucks()->detach();
                $op->delete();
            }
        }

        // Delete warehouse_stockable_tracking_number_operations that are NOT in the payload (removed by user)
        $existingWarehouseOps = $charge->warehouse_stockable_tracking_number_operations;
        foreach ($existingWarehouseOps as $op) {
            if (!in_array($op->id, $payloadWarehouseIds)) {
                $op->delete();
            }
        }

        // ✅ 4. Create or Update operations from submitted data
        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $warehouse_category_operation) {
                if (isset($warehouse_category_operation["charge_details"])) {
                    foreach ($warehouse_category_operation["charge_details"] as $charge_detail) {
                        $truck_ids = [];
                        if (isset($charge_detail["truck_ids"])) {
                            $truck_ids = $charge_detail["truck_ids"];
                            unset($charge_detail["truck_ids"]);
                        }

                        if (!isset($charge_detail["warehouse_id"]) || $charge_detail["warehouse_id"] == null) {
                            // === Non-warehouse stockable operation ===
                            unset($charge_detail["warehouse_id"]);

                            $existingOp = null;
                            if (isset($charge_detail["id"]) && is_numeric($charge_detail["id"])) {
                                $existingOp = $charge->non_warehouse_stockable_operations()->find($charge_detail["id"]);
                            }

                            if ($existingOp) {
                                // UPDATE existing operation
                                $updateData = collect($charge_detail)->only([
                                    'total_weight', 'total_weight_bill', 'unit_price',
                                    'total_price', 'total_package_count', 'stockable_id',
                                    'stockable_type', 'warehouse_category_operation_id', 'package_type_id',
                                    'description'
                                ])->toArray();
                                $existingOp->update($updateData);
                                $existingOp->trucks()->sync($truck_ids);
                            } else {
                                // CREATE new operation
                                unset($charge_detail["id"]);
                                $non_warehouse_stockable_operation = $charge->non_warehouse_stockable_operations()->create($charge_detail);
                                if (count($truck_ids) > 0) {
                                    $non_warehouse_stockable_operation->trucks()->attach($truck_ids);
                                }
                            }
                        } else {
                            // === Warehouse stockable tracking number operation ===
                            $existingOp = null;
                            if (isset($charge_detail["id"]) && is_numeric($charge_detail["id"])) {
                                $existingOp = $charge->warehouse_stockable_tracking_number_operations()->find($charge_detail["id"]);
                            }

                            if ($existingOp) {
                                // UPDATE existing warehouse operation
                                $updateData = collect($charge_detail)->only([
                                    'total_weight', 'total_weight_bill', 'unit_price',
                                    'total_price', 'total_package_count', 'stockable_id',
                                    'stockable_type', 'warehouse_category_operation_id', 'package_type_id',
                                    'description'
                                ])->toArray();
                                $existingOp->update($updateData);
                            } else {
                                // CREATE new warehouse operation
                                unset($charge_detail["id"]);
                                $warehouse = Warehouse::find($charge_detail["warehouse_id"]);
                                $warehouse_stockable = $warehouse->warehouse_stockables()->whereHasMorph('stockable', [FishType::class, GuanoType::class, PackageType::class], fn($query) => $query->where('stockable_id', $charge_detail["stockable_id"]))
                                    ->first();

                                if (isset($warehouse_stockable) == false) {
                                    $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                                        "unit_price" => 0.00,
                                        "total_weight" => 0.00,
                                        "total_price" => 0.00,
                                        "total_package_count" => 0.00,
                                        "warehouse_id" => $warehouse->id,
                                        "stockable_id" => $charge_detail["stockable_id"],
                                        "stockable_type" => $charge_detail["stockable_type"]
                                    ]);
                                }

                                $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                                    "tracking_numbers" => $data["reception_tracking_number"],
                                    "unit_price" => 0.00,
                                    "total_weight" => 0.00,
                                    "total_price" => 0.00,
                                    "total_package_count" => 0.00,
                                    "warehouse_stockable_id" => $warehouse_stockable->id,
                                ]);

                                $stockable = $this->passes($charge_detail['stockable_type'], $charge_detail['stockable_id']);
                                $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                                    "type_operation" => "in",
                                    "date" => $charge->date,
                                    "unit_price" => isset($charge_detail['unit_price']) ? $charge_detail['unit_price'] : 0,
                                    "total_weight" => $charge_detail['total_weight'],
                                    "total_price" => $charge_detail['total_price'],
                                    "stockable_type" => $charge_detail["stockable_type"],
                                    "stockable_id" => $stockable->id,
                                    "total_package_count" => $charge_detail['total_package_count'],
                                    "package_type_id" => $charge_detail['package_type_id'] ?? null,
                                    "warehouse_category_operation_id" => $charge_detail['warehouse_category_operation_id'],
                                ]);
                                $charge->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                            }
                        }
                        $charge->calc();
                    }
                }
            }
        }

        // ✅ 5. Sync suppliers
        $charge->suppliers()->sync($data['supplier_ids']);

        return response()->json([
            'message' => 'Charge updated successfully',
            'data' => $charge->fresh()->load(['suppliers', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    public function get_charges(): JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');
        $charges = Charge::whereBetween('date', [$date_start, $date_end])
            ->with(['suppliers', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations'])
            ->orderBy("date")->get();
        $tracking_numbers = SupplierInvoice::generateNextTrackingNumbers();
        return response()->json(["charges" => $charges, "tracking_numbers" => $tracking_numbers], 200);
    }

    public function get_production_charges(): JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end   = request()->query('date_end');

        $charges = Charge::whereBetween('date', [$date_start, $date_end])
            ->whereHas('charge_category', fn($q) => $q->where('is_production', true))
            ->with(['suppliers', 'charge_category', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations', 'production'])
            ->orderBy('date')
            ->get();

        return response()->json(["charges" => $charges], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Charge $charge)
    {
        $ids = request()->query('ids');
        $status = Charge::whereIn("id", $ids)->delete();
        return response()->json(["message" => $status ? "Deleted successfully" : "No records deleted"], 200);
    }

    /**
     * Check if a tracking number is unique within a charge category.
     */
    public function checkTrackingNumberUniqueness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reception_tracking_number' => 'required|string|max:50',
            'charge_category_id' => 'required|integer|exists:charge_categories,id',
            'exclude_charge_id' => 'sometimes|nullable|integer',
        ]);

        $query = Charge::where('charge_category_id', $data['charge_category_id'])
            ->whereJsonContains('reception_tracking_number', $data['reception_tracking_number']);

        if (!empty($data['exclude_charge_id'])) {
            $query->where('id', '!=', $data['exclude_charge_id']);
        }

        $existingCharge = $query->withOnly('suppliers')->first();

        if ($existingCharge) {
            return response()->json([
                'unique' => false,
                'charge' => [
                    'id' => $existingCharge->id,
                    'reception_tracking_number' => $existingCharge->reception_tracking_number,
                    'date' => $existingCharge->date?->format('Y-m-d'),
                    'suppliers' => $existingCharge->suppliers->pluck('name')->toArray(),
                    'total_weight' => $existingCharge->total_weight,
                    'total_price' => $existingCharge->total_price,
                ],
            ], 200);
        }

        return response()->json(['unique' => true], 200);
    }

    /**
     * Check if a production tracking number is unique within a charge category.
     */
    public function checkProductionTrackingNumberUniqueness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'production_tracking_number' => 'required|string|max:50',
            'charge_category_id' => 'required|integer|exists:charge_categories,id',
            'exclude_charge_id' => 'sometimes|nullable|integer',
        ]);

        $query = Charge::where('charge_category_id', $data['charge_category_id'])
            ->whereJsonContains('production_tracking_numbers', $data['production_tracking_number']);

        if (!empty($data['exclude_charge_id'])) {
            $query->where('id', '!=', $data['exclude_charge_id']);
        }

        $existingCharge = $query->withOnly('suppliers')->first();

        if ($existingCharge) {
            return response()->json([
                'unique' => false,
                'charge' => [
                    'id' => $existingCharge->id,
                    'production_tracking_numbers' => $existingCharge->production_tracking_numbers,
                    'date' => $existingCharge->date?->format('Y-m-d'),
                    'suppliers' => $existingCharge->suppliers->pluck('name')->toArray(),
                    'total_weight' => $existingCharge->total_weight,
                    'total_price' => $existingCharge->total_price,
                ],
            ], 200);
        }

        return response()->json(['unique' => true], 200);
    }

    /**
     * Get audit trail for a specific charge.
     */
    public function getAuditLogs(Charge $charge): JsonResponse
    {
        $logs = $charge->audit_logs()->get();
        return response()->json([
            'charge' => [
                'id' => $charge->id,
                'date' => $charge->date?->format('Y-m-d'),
                'reception_tracking_number' => $charge->reception_tracking_number,
                'created_by_name' => $charge->created_by_name,
                'created_at' => $charge->created_at,
            ],
            'logs' => $logs,
        ], 200);
    }
}
