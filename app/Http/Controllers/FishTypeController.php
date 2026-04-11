<?php

namespace App\Http\Controllers;

use App\Models\FishType;
use Illuminate\Http\Request;

class FishTypeController extends Controller
{
    /**
     * Display a listing of fish types with relationships.
     */
    public function index()
    {
        $fishTypes = FishType::with(['fish_family', 'fish_process_type'])->get();
        return response()->json(['fish_types' => $fishTypes], 200);
    }

    /**
     * Store a newly created fish type.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name_fr' => 'required|string|max:255|unique:fish_types,name_fr',
            'name_en' => 'required|string|max:255|unique:fish_types,name_en',
            'name_es' => 'required|string|max:255|unique:fish_types,name_es',
            'name_in_invoice' => 'required|string|max:255|unique:fish_types,name_in_invoice',
            'scientific_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'fish_family_id' => 'nullable|exists:fish_families,id',
            'fish_process_type_id' => 'nullable|exists:fish_process_types,id',
            'productivity_color' => 'nullable|string|max:50',
        ]);

        $fishType = FishType::create($data);
        $fishType->load(['fish_family', 'fish_process_type']);

        return response()->json(['fish_type' => $fishType], 201);
    }

    /**
     * Update the specified fish type.
     */
    public function update(Request $request, $id)
    {
        $fishType = FishType::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'required|string|max:255|unique:fish_types,name_fr,' . $id,
            'name_en' => 'required|string|max:255|unique:fish_types,name_en,' . $id,
            'name_es' => 'required|string|max:255|unique:fish_types,name_es,' . $id,
            'name_in_invoice' => 'required|string|max:255|unique:fish_types,name_in_invoice,' . $id,
            'scientific_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'fish_family_id' => 'nullable|exists:fish_families,id',
            'fish_process_type_id' => 'nullable|exists:fish_process_types,id',
            'productivity_color' => 'nullable|string|max:50',
        ]);

        $fishType->update($data);
        $fishType->load(['fish_family', 'fish_process_type']);

        return response()->json(['fish_type' => $fishType], 200);
    }

    /**
     * Remove the specified fish type.
     */
    public function destroy($id)
    {
        $fishType = FishType::findOrFail($id);
        $fishType->delete();

        return response()->json(['message' => 'Fish type deleted'], 200);
    }
}
