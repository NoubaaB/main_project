<?php

namespace App\Http\Controllers;

use App\Models\ProductionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductionCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductionCategory::withCount('productions')->get();
        return response()->json(['production_categories' => $categories], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'enable_reception_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_production_tracking_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_supplier_process_id' => 'boolean',
        ]);

        $category = ProductionCategory::create($data);
        return response()->json(['production_category' => $category], 201);
    }

    public function update(Request $request, ProductionCategory $productionCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'enable_reception_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_production_tracking_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_supplier_process_id' => 'boolean',
        ]);

        $productionCategory->update($data);
        return response()->json(['production_category' => $productionCategory], 200);
    }

    public function destroy(ProductionCategory $productionCategory): JsonResponse
    {
        $productionCategory->delete();
        return response()->json(['message' => 'Production category deleted successfully'], 200);
    }
}
