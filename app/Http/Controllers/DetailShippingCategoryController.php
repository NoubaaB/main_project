<?php

namespace App\Http\Controllers;

use App\Models\DetailShippingCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class DetailShippingCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index(Request $request)
    {
        $query = DetailShippingCategory::query();
        if ($request->has('shipping_category_id')) {
            $query->where('shipping_category_id', $request->shipping_category_id);
        }
        return response()->json(['detail_shipping_categories' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_type' => 'required|string|in:text,FishType,GuanoType,PackageType',
            'allow_weight' => 'boolean',
            'allow_unit_price' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
            'allow_detail_truck_id' => 'boolean',
            'fish_type_filter' => 'nullable|string|in:all,fresh,frozen',
            'include_in_invoice' => 'boolean',
            'shipping_category_id' => 'required|exists:shipping_categories,id',
        ]);
        $item = DetailShippingCategory::create($data);
        $this->broadcastChange('DetailShippingCategory', 'created', $item->id, $item->toArray());
        return response()->json(['detail_shipping_category' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = DetailShippingCategory::findOrFail($id);
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_type' => 'required|string|in:text,FishType,GuanoType,PackageType',
            'allow_weight' => 'boolean',
            'allow_unit_price' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
            'allow_detail_truck_id' => 'boolean',
            'fish_type_filter' => 'nullable|string|in:all,fresh,frozen',
            'include_in_invoice' => 'boolean',
            'shipping_category_id' => 'required|exists:shipping_categories,id',
        ]);
        $item->update($data);
        $this->broadcastChange('DetailShippingCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['detail_shipping_category' => $item]);
    }

    public function destroy($id)
    {
        $item = DetailShippingCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('DetailShippingCategory', 'deleted', (int)$id);
        return response()->json(['message' => 'Deleted']);
    }
}


