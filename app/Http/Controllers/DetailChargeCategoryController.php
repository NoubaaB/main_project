<?php

namespace App\Http\Controllers;

use App\Models\DetailChargeCategory;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class DetailChargeCategoryController extends Controller
{
    use BroadcastsModelChanges;
    public function index(Request $request)
    {
        $query = DetailChargeCategory::query();
        if ($request->has('charge_category_id')) {
            $query->where('charge_category_id', $request->charge_category_id);
        }
        return response()->json(['detail_charge_categories' => $query->get()]);
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
            'charge_category_id' => 'required|exists:charge_categories,id',
        ]);

        $item = DetailChargeCategory::create($data);
        $this->broadcastChange('DetailChargeCategory', 'created', $item->id, $item->toArray());
        return response()->json(['detail_charge_category' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = DetailChargeCategory::findOrFail($id);
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
            'charge_category_id' => 'required|exists:charge_categories,id',
        ]);

        $item->update($data);
        $this->broadcastChange('DetailChargeCategory', 'updated', $item->id, $item->toArray());
        return response()->json(['detail_charge_category' => $item]);
    }

    public function destroy($id)
    {
        $item = DetailChargeCategory::findOrFail($id);
        $item->delete();
        $this->broadcastChange('DetailChargeCategory', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }
}
