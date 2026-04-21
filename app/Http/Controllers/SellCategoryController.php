<?php

namespace App\Http\Controllers;

use App\Models\SellCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SellCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = SellCategory::withCount('sells')->orderBy('name')->get();
        return response()->json(['sell_categories' => $categories], 200);
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
            'enable_tracking_numbers' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_client_process_id' => 'boolean',
            'enable_supplier_process_id' => 'boolean',
        ]);

        $category = SellCategory::create($data);

        return response()->json(['sell_category' => $category->loadCount('sells')], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SellCategory $sellCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'enable_tracking_numbers' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_client_process_id' => 'boolean',
            'enable_supplier_process_id' => 'boolean',
        ]);

        $sellCategory->update($data);

        return response()->json(['sell_category' => $sellCategory->fresh()->loadCount('sells')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SellCategory $sellCategory): JsonResponse
    {
        $sellCategory->delete();
        return response()->json(['message' => 'Sell category deleted successfully'], 200);
    }
}
