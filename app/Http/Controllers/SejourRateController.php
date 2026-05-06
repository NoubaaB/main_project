<?php

namespace App\Http\Controllers;

use App\Models\SejourRate;
use App\Models\Warehouse;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class SejourRateController extends Controller
{
    use BroadcastsModelChanges;
    public function index(Warehouse $warehouse)
    {
        $rates = $warehouse->sejourRates()->orderBy('start_date', 'desc')->get();
        return response()->json(['rates' => $rates]);
    }

    public function store(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'daily_rate_per_kg' => 'required|numeric|min:0',
            'service_unit_price' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|integer|min:1',
        ]);

        // Check for date overlap
        $query = $warehouse->sejourRates();
        if ($data['end_date']) {
            $query->where(function ($q) use ($data) {
                $q->where(function ($q2) use ($data) {
                    $q2->where('start_date', '<=', $data['end_date'])
                       ->where(function ($q3) use ($data) {
                           $q3->where('end_date', '>=', $data['start_date'])
                              ->orWhereNull('end_date');
                       });
                });
            });
        } else {
            // Open-ended: overlaps with any rate that ends after or has no end
            $query->where(function ($q) use ($data) {
                $q->where('end_date', '>=', $data['start_date'])
                  ->orWhereNull('end_date');
            });
        }

        if ($query->exists()) {
            return response()->json(['error' => 'date_overlap'], 422);
        }

        $data['warehouse_id'] = $warehouse->id;
        $data['weight_unit'] = $data['weight_unit'] ?? 1000;
        $rate = SejourRate::create($data);
        $this->broadcastChange('SejourRate', 'created', $rate->id, $rate->toArray());
        return response()->json(['rate' => $rate], 201);
    }

    public function update(Request $request, $id)
    {
        $rate = SejourRate::findOrFail($id);
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'daily_rate_per_kg' => 'required|numeric|min:0',
            'service_unit_price' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|integer|min:1',
        ]);

        // Check for date overlap (excluding current rate)
        $query = SejourRate::where('warehouse_id', $rate->warehouse_id)
            ->where('id', '!=', $rate->id);

        if ($data['end_date']) {
            $query->where(function ($q) use ($data) {
                $q->where('start_date', '<=', $data['end_date'])
                   ->where(function ($q2) use ($data) {
                       $q2->where('end_date', '>=', $data['start_date'])
                          ->orWhereNull('end_date');
                   });
            });
        } else {
            $query->where(function ($q) use ($data) {
                $q->where('end_date', '>=', $data['start_date'])
                  ->orWhereNull('end_date');
            });
        }

        if ($query->exists()) {
            return response()->json(['error' => 'date_overlap'], 422);
        }

        $data['weight_unit'] = $data['weight_unit'] ?? 1000;
        $rate->update($data);
        $this->broadcastChange('SejourRate', 'updated', $rate->id, $rate->toArray());

        return response()->json(['rate' => $rate]);
    }

    public function destroy($id)
    {
        $item = SejourRate::findOrFail($id);
        $item->delete();
        $this->broadcastChange('SejourRate', 'deleted', (int)$id);
        return response()->json(['message' => 'Deleted']);
    }
}
