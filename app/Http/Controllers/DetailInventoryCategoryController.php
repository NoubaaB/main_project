<?php

namespace App\Http\Controllers;

use App\Models\DetailInventoryCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class DetailInventoryCategoryController extends Controller
{
    use BroadcastsModelChanges;

    public function index(Request $request)
    {
        return response()->json(['detail_inventory_categories' => DetailInventoryCategory::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_type' => 'required|string|in:text,FishType,GuanoType,PackageType',
            'fish_type_filter' => 'nullable|string|in:all,fresh,frozen',
            'allow_weight' => 'boolean',
            'allow_unit_price' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
        ]);

        $item = DetailInventoryCategory::create($data);
        $this->broadcastChange('DetailInventoryCategory', 'created', $item->id, $item->toArray());
        return response()->json(['detail_inventory_category' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = DetailInventoryCategory::findOrFail($id);
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_type' => 'required|string|in:text,FishType,GuanoType,PackageType',
            'fish_type_filter' => 'nullable|string|in:all,fresh,frozen',
            'allow_weight' => 'boolean',
            'allow_unit_price' => 'boolean',
            'allow_price' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
        ]);

        $item->update($data);
        $this->broadcastChange('DetailInventoryCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['detail_inventory_category' => $item]);
    }

    public function destroy($id)
    {
        $item = DetailInventoryCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('DetailInventoryCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}
