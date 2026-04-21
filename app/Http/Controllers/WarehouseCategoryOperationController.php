<?php

namespace App\Http\Controllers;

use App\Models\WarehouseCategoryOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseCategoryOperationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = WarehouseCategoryOperation::withCount([
            'warehouse_stockable_tracking_number_operations',
            'non_warehouse_stockable_operations',
        ])->get();

        return response()->json([
            'warehouse_category_operations' => $items,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'allow_weight' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_warehouse' => 'boolean',
            'allow_detail_truck_id' => 'boolean',
            'production_role' => 'nullable|in:fresh,frozen,guano',
            'operation_type' => 'required|in:charge,sell,both',
            'warehouse_type' => 'nullable|in:FishType,GuanoType,PackageType',
        ]);

        $item = WarehouseCategoryOperation::create($data);

        return response()->json([
            'warehouse_category_operation' => $item,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $item = WarehouseCategoryOperation::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'allow_weight' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_warehouse' => 'boolean',
            'allow_detail_truck_id' => 'boolean',
            'production_role' => 'nullable|in:fresh,frozen,guano',
            'operation_type' => 'required|in:charge,sell,both',
            'warehouse_type' => 'nullable|in:FishType,GuanoType,PackageType',
        ]);

        $item->update($data);

        return response()->json([
            'warehouse_category_operation' => $item,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $item = WarehouseCategoryOperation::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ], 200);
    }
}
