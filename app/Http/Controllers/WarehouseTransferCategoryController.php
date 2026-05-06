<?php

namespace App\Http\Controllers;

use App\Models\WarehouseTransferCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class WarehouseTransferCategoryController extends Controller
{
    use BroadcastsModelChanges;

    public function index()
    {
        return response()->json(['warehouse_transfer_categories' => WarehouseTransferCategory::all()]);
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
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'allow_trucks' => 'boolean',
        ]);

        $item = WarehouseTransferCategory::create($data);
        $this->broadcastChange('WarehouseTransferCategory', 'created', $item->id, $item->toArray());
        return response()->json(['warehouse_transfer_category' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = WarehouseTransferCategory::findOrFail($id);
        $data = $request->validate([
            'name_fr' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_type' => 'required|string|in:text,FishType,GuanoType,PackageType',
            'fish_type_filter' => 'nullable|string|in:all,fresh,frozen',
            'allow_weight' => 'boolean',
            'allow_package_type' => 'boolean',
            'allow_nbr_box' => 'boolean',
            'allow_warehouse' => 'boolean',
            'allow_trucks' => 'boolean',
        ]);

        $item->update($data);
        $this->broadcastChange('WarehouseTransferCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['warehouse_transfer_category' => $item]);
    }

    public function destroy($id)
    {
        $item = WarehouseTransferCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('WarehouseTransferCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}
