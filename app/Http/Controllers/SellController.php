<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FishType;
use App\Models\GuanoType;
use App\Models\PackageType;
use App\Models\Sell;
use App\Models\SellAuditLog;
use App\Models\Warehouse;
use App\Models\WarehouseStockableTrackingNumber;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SellController extends Controller
{
    /**
     * Resolve a stockable model by type and id.
     */
    public function passes($stockable_type, $stockable_id)
    {
        $modelClass = 'App\\Models\\' . Str::studly($stockable_type);
        if (!class_exists($modelClass)) {
            return false;
        }

        return $modelClass::find($stockable_id);
    }

    /**
     * Display the specified sell.
     */
    public function show(Sell $sell)
    {
        return response()->json([
            "sell" => $sell->load(['clients', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations'])
        ], 200);
    }

    /**
     * Create a new sell and deduct stock from warehouse.
     */
    public function sell(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'tracking_numbers' => 'nullable|array',
            'tracking_numbers.*' => 'string|max:50',
            'tracking_onp_numbers' => 'nullable|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'origin_id' => 'nullable|exists:origins,id',
            'truck_ids' => 'sometimes|array|exists:trucks,id',
            'client_ids' => 'sometimes|array',
            'client_ids.*' => 'exists:clients,id',
            'sell_category_id' => 'required|exists:sell_categories,id',
            'warehouse_category_operations' => 'sometimes|array',
            'warehouse_category_operations.*' => 'array',
            'warehouse_category_operations.*.id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.sell_details' => 'sometimes|array',
            'warehouse_category_operations.*.sell_details.*' => 'array',
            'warehouse_category_operations.*.sell_details.*.warehouse_category_operation_id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.sell_details.*.truck_ids' => 'sometimes|array|exists:trucks,id',
            'warehouse_category_operations.*.sell_details.*.total_weight' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.sell_details.*.total_weight_bill' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.sell_details.*.total_price' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.sell_details.*.total_package_count' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.sell_details.*.unit_price' => 'sometimes|nullable|numeric|min:0',
            'warehouse_category_operations.*.sell_details.*.stockable_id' => 'required|integer',
            'warehouse_category_operations.*.sell_details.*.stockable_type' => 'required|string',
            'warehouse_category_operations.*.sell_details.*.warehouse_id' => 'sometimes|nullable|exists:warehouses,id',
            'warehouse_category_operations.*.sell_details.*.warehouse_stockable_tracking_number_id' => 'sometimes|nullable|integer',
            'warehouse_category_operations.*.sell_details.*.package_type_id' => 'sometimes|nullable|exists:package_types,id',
        ]);

        // ✅ 1. Create parent sell record
        $sell = Sell::create([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'] ?? null,
            'tracking_numbers' => $data['tracking_numbers'] ?? [],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? [],
            'fishery_id' => $data['fishery_id'] ?? null,
            'origin_id' => $data['origin_id'] ?? null,
            'sell_category_id' => $data['sell_category_id'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // ✅ Audit log: created
        SellAuditLog::create([
            'sell_id' => $sell->id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'created',
            'changes' => [
                'date' => ['old' => null, 'new' => $sell->date?->format('Y-m-d')],
                'tracking_numbers' => ['old' => null, 'new' => $sell->tracking_numbers],
                'tracking_onp_numbers' => ['old' => null, 'new' => $sell->tracking_onp_numbers],
                'note' => ['old' => null, 'new' => $sell->note],
                'sell_category_id' => ['old' => null, 'new' => $sell->sell_category_id],
            ],
        ]);

        // ✅ 2. Attach trucks
        if (isset($data["truck_ids"])) {
            $sell->trucks()->attach($data['truck_ids']);
        }

        // ✅ 3. Attach clients
        if (isset($data["client_ids"])) {
            $sell->clients()->sync($data['client_ids']);
        }

        // ✅ 4. Create warehouse_category_operations children (sell_details)
        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $warehouse_category_operation) {
                if (isset($warehouse_category_operation["sell_details"])) {
                    foreach ($warehouse_category_operation["sell_details"] as $sell_detail) {
                        $truck_ids = [];
                        if (isset($sell_detail["truck_ids"])) {
                            $truck_ids = $sell_detail["truck_ids"];
                            unset($sell_detail["truck_ids"]);
                        }

                        // Save the tracking number ID before removing frontend-only fields
                        $tracking_number_lot_id = $sell_detail["warehouse_stockable_tracking_number_id"] ?? null;

                        // Remove frontend-only fields
                        unset($sell_detail["id"]);
                        unset($sell_detail["warehouse_stockable_tracking_number_id"]);

                        if (!isset($sell_detail["warehouse_id"]) || $sell_detail["warehouse_id"] == null) {
                            // === Non-warehouse operation (no warehouse selected) ===
                            unset($sell_detail["warehouse_id"]);
                            $non_warehouse_stockable_operation = $sell->non_warehouse_stockable_operations()->create($sell_detail);
                            if (count($truck_ids) > 0) {
                                $non_warehouse_stockable_operation->trucks()->attach($truck_ids);
                            }
                        } else {
                            // === Warehouse operation — deduct stock (type_operation = "out") ===
                            $warehouse = Warehouse::find($sell_detail["warehouse_id"]);
                            $warehouse_stockable = $warehouse->warehouse_stockables()
                                ->whereHasMorph('stockable', [FishType::class, GuanoType::class, PackageType::class], fn($query) => $query->where('stockable_id', $sell_detail["stockable_id"]))
                                ->first();

                            if (!$warehouse_stockable) {
                                continue;
                            }

                            // Find the tracking number lot to deduct from
                            // The frontend sends warehouse_stockable_tracking_number_id (the DB id)
                            $warehouse_stockable_tracking_number = null;
                            if ($tracking_number_lot_id) {
                                $warehouse_stockable_tracking_number = WarehouseStockableTrackingNumber::find($tracking_number_lot_id);
                            }

                            // Fallback: find by the sell's tracking numbers or get the first available
                            if (!$warehouse_stockable_tracking_number) {
                                $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->first();
                            }

                            if (!$warehouse_stockable_tracking_number) {
                                continue;
                            }

                            // Create the "out" operation (deduct from warehouse)
                            $stockable = $this->passes($sell_detail['stockable_type'], $sell_detail['stockable_id']);
                            $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                                "type_operation" => "out",
                                "date" => $sell->date,
                                "unit_price" => isset($sell_detail['unit_price']) ? $sell_detail['unit_price'] : 0,
                                "total_weight" => $sell_detail['total_weight'] ?? 0,
                                "total_price" => $sell_detail['total_price'] ?? 0,
                                "stockable_type" => $sell_detail["stockable_type"],
                                "stockable_id" => $stockable->id,
                                "total_package_count" => $sell_detail['total_package_count'] ?? 0,
                                "package_type_id" => $sell_detail['package_type_id'] ?? null,
                                "warehouse_category_operation_id" => $sell_detail['warehouse_category_operation_id'],
                            ]);
                            $sell->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                        }
                        $sell->calc();
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Sell created successfully',
            'data' => $sell->load(['clients', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    /**
     * Get sells with date range filter.
     */
    public function get_sells(): JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');
        $sells = Sell::whereBetween('date', [$date_start, $date_end])
            ->with(['clients', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations'])
            ->orderBy("date")->get();
        return response()->json(["sells" => $sells], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sell $sell)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(["message" => "No IDs provided"], 400);
        }
        // Record audit logs for each sell being deleted
        $sells = Sell::whereIn('id', $ids)->get();
        foreach ($sells as $s) {
            SellAuditLog::create([
                'sell_id' => $s->id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'deleted',
                'changes' => [
                    'date' => ['old' => $s->date?->format('Y-m-d'), 'new' => null],
                    'tracking_numbers' => ['old' => $s->tracking_numbers, 'new' => null],
                    'note' => ['old' => $s->note, 'new' => null],
                ],
            ]);
        }
        $status = Sell::whereIn('id', $ids)->delete();
        return response()->json([
            "message" => $status ? "Deleted successfully" : "No records deleted"
        ], 200);
    }

    /**
     * Get audit trail for a specific sell.
     */
    public function getAuditLogs(Sell $sell): JsonResponse
    {
        $logs = $sell->audit_logs()->get();
        return response()->json([
            'sell' => [
                'id' => $sell->id,
                'date' => $sell->date?->format('Y-m-d'),
                'tracking_numbers' => $sell->tracking_numbers,
                'created_at' => $sell->created_at,
            ],
            'logs' => $logs,
        ], 200);
    }

    /**
     * Check if a tracking number already exists for a given sell category.
     */
    public function checkTrackingNumberUniqueness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => 'required|string|max:50',
            'sell_category_id' => 'required|integer|exists:sell_categories,id',
            'exclude_sell_id' => 'sometimes|nullable|integer',
        ]);

        $query = Sell::where('sell_category_id', $data['sell_category_id'])
            ->whereJsonContains('tracking_numbers', $data['tracking_number']);

        if (!empty($data['exclude_sell_id'])) {
            $query->where('id', '!=', $data['exclude_sell_id']);
        }

        $existingSell = $query->first();

        if ($existingSell) {
            return response()->json([
                'unique' => false,
                'sell' => [
                    'id' => $existingSell->id,
                    'tracking_numbers' => $existingSell->tracking_numbers,
                    'date' => $existingSell->date?->format('Y-m-d'),
                    'clients' => $existingSell->clients->pluck('name')->toArray(),
                    'total_weight' => $existingSell->total_weight,
                    'total_price' => $existingSell->total_price,
                ],
            ], 200);
        }

        return response()->json(['unique' => true], 200);
    }
}
