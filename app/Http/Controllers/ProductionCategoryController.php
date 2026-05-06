<?php

namespace App\Http\Controllers;

use App\Models\ProductionCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class ProductionCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index()
    {
        return response()->json(['production_categories' => ProductionCategory::withCount('productions')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'enable_reception_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_production_tracking_number' => 'boolean',
            'enable_production_type' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_suppliers' => 'boolean',
            'enable_clients' => 'boolean',
            'enable_invoices' => 'boolean',
            'enable_quality_control' => 'boolean',
        ]);
        $cat = ProductionCategory::create($data);
        $this->broadcastChange('ProductionCategory', 'created', $cat->id, $cat->toArray());
        return response()->json(['production_category' => $cat], 201);
    }

    public function update(Request $request, ProductionCategory $productionCategory)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'enable_reception_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_production_tracking_number' => 'boolean',
            'enable_production_type' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_suppliers' => 'boolean',
            'enable_clients' => 'boolean',
            'enable_invoices' => 'boolean',
            'enable_quality_control' => 'boolean',
            'enable_sell_tracking_number' => 'boolean',
        ]);
        $productionCategory->update($data);
        $this->broadcastChange('ProductionCategory', 'updated', $productionCategory->id, $productionCategory->toArray());
        return response()->json(['production_category' => $productionCategory]);
    }

    public function destroy(ProductionCategory $productionCategory)
    {
        $id = $productionCategory->id;
        $productionCategory->delete();
        $this->broadcastChange('ProductionCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}
