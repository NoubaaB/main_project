<?php

namespace App\Http\Controllers;

use App\Models\FishProcessType;
use Illuminate\Http\Request;

class FishProcessTypeController extends Controller
{
    /**
     * Display a listing of fish process types with fish_types count.
     */
    public function index()
    {
        $processTypes = FishProcessType::withCount('fish_types')->get();
        return response()->json(['fish_process_types' => $processTypes], 200);
    }

    /**
     * Store a newly created fish process type (used by autocomplete create-on-enter).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:fish_process_types,name',
            'description' => 'nullable|string|max:500',
        ]);

        $processType = FishProcessType::create($data);
        $processType->loadCount('fish_types');

        return response()->json(['fish_process_type' => $processType], 201);
    }

    /**
     * Update the specified fish process type.
     */
    public function update(Request $request, $id)
    {
        $processType = FishProcessType::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:fish_process_types,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $processType->update($data);
        $processType->loadCount('fish_types');

        return response()->json(['fish_process_type' => $processType], 200);
    }

    /**
     * Remove the specified fish process type.
     */
    public function destroy($id)
    {
        $processType = FishProcessType::findOrFail($id);
        $processType->delete();

        return response()->json(['message' => 'Fish process type deleted'], 200);
    }
}
