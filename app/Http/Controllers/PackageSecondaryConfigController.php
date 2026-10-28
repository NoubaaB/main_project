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
            'components' => 'nullable|array',
            'components.*.key' => 'nullable|string',
            'components.*.name' => 'nullable|string',
            'components.*.unit' => 'nullable|string',
            'components.*.qte' => 'nullable|numeric|min:0',
            'components.*.price' => 'nullable|numeric|min:0',
            'components.*.is_custom' => 'nullable|boolean',
        ]);

        $item = new PackageSecondaryConfig($data);
        if (isset($data['components']) && is_array($data['components'])) {
            foreach ($data['components'] as $comp) {
                $k = $comp['key'] ?? '';
                if (in_array($k, ['caisses', 'cellophane', 'plastique', 'strapex', 'palletes', 'scotch'])) {
                    $item->{"{$k}_qte"} = (float) ($comp['qte'] ?? 0);
                    $item->{"{$k}_price"} = (float) ($comp['price'] ?? 0);
                }
            }
        }
        $item->unit_cost = $item->computeUnitCost();
        $item->save();
        $item->load('packageType');

        $recalcResult = app(\App\Services\SecondaryPackagingService::class)->recalculateForPackageType(
            (int) $item->package_type_id,
            $item->date_start,
            $item->date_end
        );

        $this->broadcastChange('PackageSecondaryConfig', 'created', $item->id, $item->toArray());
        return response()->json([
            'package_secondary_config' => $item,
            'recalculation' => $recalcResult,
        ], 201);
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
            'components' => 'nullable|array',
            'components.*.key' => 'nullable|string',
            'components.*.name' => 'nullable|string',
            'components.*.unit' => 'nullable|string',
            'components.*.qte' => 'nullable|numeric|min:0',
            'components.*.price' => 'nullable|numeric|min:0',
            'components.*.is_custom' => 'nullable|boolean',
        ]);

        $item->fill($data);
        if (isset($data['components']) && is_array($data['components'])) {
            foreach ($data['components'] as $comp) {
                $k = $comp['key'] ?? '';
                if (in_array($k, ['caisses', 'cellophane', 'plastique', 'strapex', 'palletes', 'scotch'])) {
                    $item->{"{$k}_qte"} = (float) ($comp['qte'] ?? 0);
                    $item->{"{$k}_price"} = (float) ($comp['price'] ?? 0);
                }
            }
        }
        $item->unit_cost = $item->computeUnitCost();
        $item->save();
        $item->load('packageType');

        $recalcResult = app(\App\Services\SecondaryPackagingService::class)->recalculateForPackageType(
            (int) $item->package_type_id,
            $item->date_start,
            $item->date_end
        );

        $this->broadcastChange('PackageSecondaryConfig', 'updated', $item->id, $item->toArray());
        return response()->json([
            'package_secondary_config' => $item,
            'recalculation' => $recalcResult,
        ]);
    }

    public function destroy($id)
    {
        $item = PackageSecondaryConfig::findOrFail($id);
        $packageTypeId = (int) $item->package_type_id;
        $item->delete();

        $recalcResult = app(\App\Services\SecondaryPackagingService::class)->recalculateForPackageType(
            $packageTypeId
        );

        $this->broadcastChange('PackageSecondaryConfig', 'deleted', (int) $id);
        return response()->json([
            'message' => 'Deleted',
            'recalculation' => $recalcResult,
        ]);
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
