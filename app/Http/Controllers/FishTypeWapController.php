<?php

namespace App\Http\Controllers;

use App\Models\FishType;
use App\Models\FishTypeWap;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class FishTypeWapController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Display a listing of WAP rules for a given fish type.
     */
    public function index($fishTypeId)
    {
        $waps = FishTypeWap::with('warehouse')
            ->where('fish_type_id', $fishTypeId)
            ->orderBy('date_start', 'desc')
            ->get();

        return response()->json(['waps' => $waps], 200);
    }

    /**
     * Store a newly created WAP rule for a fish type.
     */
    public function store(Request $request, $fishTypeId)
    {
        $fishType = FishType::findOrFail($fishTypeId);

        if ($request->has('date_end') && (!is_string($request->date_end) || strtotime($request->date_end) === false)) {
            $request->merge(['date_end' => null]);
        }

        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'wap' => 'required|numeric|min:0',
        ]);

        $data['fish_type_id'] = $fishType->id;

        $wap = FishTypeWap::create($data);
        $wap->load('warehouse');

        $this->broadcastChange('FishTypeWap', 'created', $wap->id, $wap->toArray());

        return response()->json(['wap' => $wap], 201);
    }

    /**
     * Update the specified WAP rule.
     */
    public function update(Request $request, $id)
    {
        $wap = FishTypeWap::findOrFail($id);

        if ($request->has('date_end') && (!is_string($request->date_end) || strtotime($request->date_end) === false)) {
            $request->merge(['date_end' => null]);
        }

        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'wap' => 'required|numeric|min:0',
        ]);

        $wap->update($data);
        $wap->load('warehouse');

        $this->broadcastChange('FishTypeWap', 'updated', $wap->id, $wap->toArray());

        return response()->json(['wap' => $wap], 200);
    }

    /**
     * Remove the specified WAP rule.
     */
    public function destroy($id)
    {
        $wap = FishTypeWap::findOrFail($id);
        $wapId = $wap->id;
        $wap->delete();

        $this->broadcastChange('FishTypeWap', 'deleted', (int)$wapId);

        return response()->json(['message' => 'WAP rule deleted'], 200);
    }
}
