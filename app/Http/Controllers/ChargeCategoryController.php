<?php

namespace App\Http\Controllers;

use App\Models\ChargeCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChargeCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = ChargeCategory::withCount('charges')->orderBy('name')->get();
        return response()->json(['charge_categories' => $categories], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
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
            'is_production' => 'boolean',
        ]);

        $category = ChargeCategory::create($data);

        return response()->json(['charge_category' => $category->loadCount('charges')], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChargeCategory $chargeCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
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
            'is_production' => 'boolean',
        ]);

        $chargeCategory->update($data);

        return response()->json(['charge_category' => $chargeCategory->fresh()->loadCount('charges')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChargeCategory $chargeCategory): JsonResponse
    {
        $chargeCategory->delete();
        return response()->json(['message' => 'Charge category deleted successfully'], 200);
    }
}
