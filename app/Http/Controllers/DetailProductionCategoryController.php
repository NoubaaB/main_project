<?php

namespace App\Http\Controllers;

use App\Models\DetailProductionCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class DetailProductionCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index(Request $request)
    {
        $query = DetailProductionCategory::query();
        if ($request->has('production_category_id')) {
            $query->where('production_category_id', $request->production_category_id);
        }
        return response()->json(['detail_production_categories' => $query->get()]);
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
            'production_category_id' => 'required|exists:production_categories,id',
        ]);
        $item = DetailProductionCategory::create($data);
        $this->broadcastChange('DetailProductionCategory', 'created', $item->id, $item->toArray());
        return response()->json(['detail_production_category' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = DetailProductionCategory::findOrFail($id);
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
            'production_category_id' => 'required|exists:production_categories,id',
        ]);
        $item->update($data);
        $this->broadcastChange('DetailProductionCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['detail_production_category' => $item]);
    }

    public function destroy($id)
    {
        $item = DetailProductionCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('DetailProductionCategory', 'deleted', (int)$id);
        return response()->json(['message' => 'Deleted']);
    }
}
