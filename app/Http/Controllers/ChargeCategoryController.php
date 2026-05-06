<?php

namespace App\Http\Controllers;

use App\Models\ChargeCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class ChargeCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index()
    {
        $categories = ChargeCategory::withCount('charges')->get();
        return response()->json(['charge_categories' => $categories]);
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
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_suppliers' => 'boolean',
            'enable_invoices' => 'boolean',
            'is_production' => 'boolean',
            'enable_quality_control' => 'boolean',
        ]);

        $category = ChargeCategory::create($data);
        $this->broadcastChange('ChargeCategory', 'created', $category->id, $category->toArray());
        return response()->json(['charge_category' => $category], 201);
    }

    public function update(Request $request, ChargeCategory $chargeCategory)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'enable_reception_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_suppliers' => 'boolean',
            'enable_invoices' => 'boolean',
            'is_production' => 'boolean',
            'enable_quality_control' => 'boolean',
        ]);

        $chargeCategory->update($data);
        $this->broadcastChange('ChargeCategory', 'updated', $chargeCategory->id, $chargeCategory->toArray());
        return response()->json(['charge_category' => $chargeCategory]);
    }

    public function destroy(ChargeCategory $chargeCategory)
    {
        $id = $chargeCategory->id;
        $chargeCategory->delete();
        $this->broadcastChange('ChargeCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}
