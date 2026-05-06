<?php

namespace App\Http\Controllers;

use App\Models\ShippingCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class ShippingCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index()
    {
        return response()->json(['shipping_categories' => ShippingCategory::withCount('shippings')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'enable_shipping_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_clients' => 'boolean',
            'enable_direction' => 'boolean',
            'enable_invoices' => 'boolean',
        ]);
        $cat = ShippingCategory::create($data);
        $this->broadcastChange('ShippingCategory', 'created', $cat->id, $cat->toArray());
        return response()->json(['shipping_category' => $cat], 201);
    }

    public function update(Request $request, ShippingCategory $shippingCategory)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'enable_shipping_tracking_number' => 'boolean',
            'enable_tracking_onp_numbers' => 'boolean',
            'enable_fishery_id' => 'boolean',
            'enable_ship_id' => 'boolean',
            'enable_origin_id' => 'boolean',
            'enable_truck_id' => 'boolean',
            'enable_clients' => 'boolean',
            'enable_direction' => 'boolean',
            'enable_invoices' => 'boolean',
        ]);
        $shippingCategory->update($data);
        $this->broadcastChange('ShippingCategory', 'updated', $shippingCategory->id, $shippingCategory->toArray());
        return response()->json(['shipping_category' => $shippingCategory]);
    }

    public function destroy(ShippingCategory $shippingCategory)
    {
        $id = $shippingCategory->id;
        $shippingCategory->delete();
        $this->broadcastChange('ShippingCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}


