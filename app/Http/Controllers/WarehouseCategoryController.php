<?php

namespace App\Http\Controllers;

use App\Models\WarehouseCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class WarehouseCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index()
    {
        return response()->json(['warehouse_categories' => WarehouseCategory::withCount('warehouses')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:warehouse_categories,name',
            'description' => 'nullable|string',
        ]);
        $cat = WarehouseCategory::create($data);
        $this->broadcastChange('WarehouseCategory', 'created', $cat->id, $cat->toArray());
        return response()->json(['warehouse_category' => $cat], 201);
    }

    public function update(Request $request, $id)
    {
        $item = WarehouseCategory::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:warehouse_categories,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $item->update($data);
        $this->broadcastChange('WarehouseCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['warehouse_category' => $item]);
    }

    public function destroy($id)
    {
        $item = WarehouseCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('WarehouseCategory', 'deleted', (int)$id);
        return response()->json(['message' => 'Deleted']);
    }
}
