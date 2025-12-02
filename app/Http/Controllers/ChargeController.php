<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\charge;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChargeController extends Controller
{
    //
    public function passes($stockable_type, $stockable_id)
    {

        $modelClass = 'App\\Models\\' . Str::studly($stockable_type);
        if (! class_exists($modelClass)) {
            return false;
        }

        return $modelClass::find($stockable_id) !== null;
    }

    public function charge(Request $request)
    {
        // $this->passes("FishType",1);
        $data  = $request->validate([
            'date' => 'required|date',
            'tracking_numbers' => 'required|array',
            'tracking_numbers.*' => 'string|max:50',
            'tracking_onp_numbers' => 'required|array',
            'tracking_onp_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'fishery_id' => 'required|exists:fisheries,id',
            'origin_id' => 'required|exists:origins,id',
            'charge_category_id' => 'required|exists:charge_categories,id',
            'supplier_ids' => 'required|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'warehouse_category_operations.*' => 'array',
            'warehouse_category_operations.*.id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.charge_details.*.warehouse_category_operation_id' => 'required|exists:warehouse_category_operations,id',
            'warehouse_category_operations.*.charge_details.*.total_weight' => 'nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_weight_bill' => 'nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_price' => 'nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.total_package_count' => 'nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.unit_price' => 'nullable|numeric|min:0',
            'warehouse_category_operations.*.charge_details.*.stockable_id' => 'required|integer',
            'warehouse_category_operations.*.charge_details.*.stockable_type' => 'required|string',
            'warehouse_category_operations.*.charge_details.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'warehouse_category_operations.*.charge_details.*.package_type_id' => 'nullable|exists:package_types,id'
        ]);

        // ✅ 1. Create parent record
        $charge = Charge::create([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'],
            'tracking_numbers' => $data['tracking_numbers'],
            'tracking_onp_numbers' => $data['tracking_onp_numbers'],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_category_id' => $data['charge_category_id'],
        ]);

        // ✅ 2. Create warehouse_category_operations children (details)
        if (isset($data['warehouse_category_operations'])) {
            foreach ($data['warehouse_category_operations'] as $warehouse_category_operation) {
                foreach ($warehouse_category_operation["charge_details"] as $charge_detail) {
                    unset($charge_detail["warehouse_id"]);
                    // dd($charge_detail);
                    $charge->non_warehouse_stockable_operations()->create($charge_detail);
                    // if($charge_detail["warehouse_id"] == null){
                    //     $charge->non_warehouse_stockable_operations()->create($charge_detail);
                    // }else{
                    //     $charge->warehouse_stockable_tracking_number_operations()->create($charge_detail);
                    // }
                }
            }
        }

        $charge->suppliers()->sync($data['supplier_ids']);

        return response()->json([
            'message' => 'Fresh Fish Receive created successfully',
            'data' => $charge->load(['suppliers', 'warehouse_category_operation', 'non_warehouse_stockable_operations', 'warehouse_stockable_tracking_number_operations']),
        ], 200);
    }

    public function get_charges(): JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');
        $charges = Charge::whereBetween('date', [$date_start, $date_end])
            ->with(['suppliers', 'chargeDetails', 'productionDetails'])
            ->get();
        return response()->json(["charges" => $charges], 200);
    }
}
