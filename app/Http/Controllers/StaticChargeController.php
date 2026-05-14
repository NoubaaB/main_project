<?php

namespace App\Http\Controllers;

use App\Models\StaticCharge;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaticChargeController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Return all static charges ordered by created_at desc.
     */
    public function index(): JsonResponse
    {
        $charges = StaticCharge::orderBy('created_at', 'desc')->get();
        return response()->json(['static_charges' => $charges]);
    }

    /**
     * Create a new static charge.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_en'         => 'nullable|string|max:255',
            'name_fr'         => 'nullable|string|max:255',
            'name_es'         => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0',
            'date_range_type' => 'required|in:all_time,date_range,ongoing',
            'date_start'      => 'nullable|date',
            'date_end'        => 'nullable|date|after_or_equal:date_start',
            'description'     => 'nullable|string|max:1000',
            'is_active'       => 'nullable|boolean',
        ]);

        // Enforce date logic based on type
        if ($data['date_range_type'] === 'all_time') {
            $data['date_start'] = null;
            $data['date_end'] = null;
        } elseif ($data['date_range_type'] === 'ongoing') {
            $data['date_end'] = null;
        }

        $data['created_by'] = Auth::id();

        $charge = StaticCharge::create($data);

        $this->broadcastChange('StaticCharge', 'created', $charge->id, $charge->toArray());

        return response()->json([
            'message'       => 'Static charge created successfully',
            'static_charge' => $charge,
        ], 201);
    }

    /**
     * Update an existing static charge.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $charge = StaticCharge::findOrFail($id);

        $data = $request->validate([
            'name_en'         => 'nullable|string|max:255',
            'name_fr'         => 'nullable|string|max:255',
            'name_es'         => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0',
            'date_range_type' => 'required|in:all_time,date_range,ongoing',
            'date_start'      => 'nullable|date',
            'date_end'        => 'nullable|date|after_or_equal:date_start',
            'description'     => 'nullable|string|max:1000',
            'is_active'       => 'nullable|boolean',
        ]);

        // Enforce date logic based on type
        if ($data['date_range_type'] === 'all_time') {
            $data['date_start'] = null;
            $data['date_end'] = null;
        } elseif ($data['date_range_type'] === 'ongoing') {
            $data['date_end'] = null;
        }

        $charge->update($data);

        $this->broadcastChange('StaticCharge', 'updated', $charge->id, $charge->toArray());

        return response()->json([
            'message'       => 'Static charge updated successfully',
            'static_charge' => $charge,
        ]);
    }

    /**
     * Delete a static charge (soft delete).
     */
    public function destroy($id): JsonResponse
    {
        $charge = StaticCharge::findOrFail($id);
        $chargeData = $charge->toArray();
        $charge->delete();

        $this->broadcastChange('StaticCharge', 'deleted', $id, $chargeData);

        return response()->json([
            'message' => 'Static charge deleted successfully',
        ]);
    }
}
