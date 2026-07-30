<?php

namespace App\Http\Controllers;

use App\Models\PackageSecondaryConfig;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class PackageSecondaryConfigController extends Controller
{
    use BroadcastsModelChanges;

    public function index(Request $request)
    {
        $query = PackageSecondaryConfig::with('packageType');
        if ($request->has('package_type_id')) {
            $query->where('package_type_id', $request->package_type_id);
        }
        $configs = $query->orderBy('date_start', 'desc')->get();
        return response()->json(['package_secondary_configs' => $configs]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'package_type_id' => 'required|exists:package_types,id',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'caisses_qte' => 'nullable|numeric|min:0',
            'caisses_price' => 'nullable|numeric|min:0',
            'cellophane_qte' => 'nullable|numeric|min:0',
            'cellophane_price' => 'nullable|numeric|min:0',
            'plastique_qte' => 'nullable|numeric|min:0',
            'plastique_price' => 'nullable|numeric|min:0',
            'strapex_qte' => 'nullable|numeric|min:0',
            'strapex_price' => 'nullable|numeric|min:0',
            'palletes_qte' => 'nullable|numeric|min:0',
            'palletes_price' => 'nullable|numeric|min:0',
            'scotch_qte' => 'nullable|numeric|min:0',
            'scotch_price' => 'nullable|numeric|min:0',
        ]);

        $item = new PackageSecondaryConfig($data);
        $item->unit_cost = $item->computeUnitCost();
        $item->save();
        $item->load('packageType');

        $this->broadcastChange('PackageSecondaryConfig', 'created', $item->id, $item->toArray());
        return response()->json(['package_secondary_config' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = PackageSecondaryConfig::findOrFail($id);
        $data = $request->validate([
            'package_type_id' => 'required|exists:package_types,id',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'caisses_qte' => 'nullable|numeric|min:0',
            'caisses_price' => 'nullable|numeric|min:0',
            'cellophane_qte' => 'nullable|numeric|min:0',
            'cellophane_price' => 'nullable|numeric|min:0',
            'plastique_qte' => 'nullable|numeric|min:0',
            'plastique_price' => 'nullable|numeric|min:0',
            'strapex_qte' => 'nullable|numeric|min:0',
            'strapex_price' => 'nullable|numeric|min:0',
            'palletes_qte' => 'nullable|numeric|min:0',
            'palletes_price' => 'nullable|numeric|min:0',
            'scotch_qte' => 'nullable|numeric|min:0',
            'scotch_price' => 'nullable|numeric|min:0',
        ]);

        $item->fill($data);
        $item->unit_cost = $item->computeUnitCost();
        $item->save();
        $item->load('packageType');

        $this->broadcastChange('PackageSecondaryConfig', 'updated', $item->id, $item->toArray());
        return response()->json(['package_secondary_config' => $item]);
    }

    public function destroy($id)
    {
        $item = PackageSecondaryConfig::findOrFail($id);
        $item->delete();
        $this->broadcastChange('PackageSecondaryConfig', 'deleted', (int) $id);
        return response()->json(['message' => 'Deleted']);
    }

    public function getActiveConfig(Request $request)
    {
        $request->validate([
            'package_type_id' => 'required|exists:package_types,id',
            'date' => 'required|date',
        ]);

        $config = PackageSecondaryConfig::getConfigForPackageAndDate(
            $request->package_type_id,
            $request->date
        );

        return response()->json(['package_secondary_config' => $config]);
    }
}
