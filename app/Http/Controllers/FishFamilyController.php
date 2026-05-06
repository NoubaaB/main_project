<?php

namespace App\Http\Controllers;

use App\Models\FishFamily;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class FishFamilyController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * Display a listing of fish families.
     */
    public function index()
    {
        $families = FishFamily::withCount('fish_types')->get();
        return response()->json(['fish_families' => $families], 200);
    }

    /**
     * Store a newly created fish family.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name_fr' => 'required|string|max:255|unique:fish_families,name_fr',
            'name_en' => 'required|string|max:255|unique:fish_families,name_en',
            'name_es' => 'required|string|max:255|unique:fish_families,name_es',
        ]);

        $family = FishFamily::create($data);
        $this->broadcastChange('FishFamily', 'created', $family->id, $family->toArray());
        return response()->json(['fish_family' => $family], 201);
    }

    /**
     * Update the specified fish family.
     */
    public function update(Request $request, $id)
    {
        $family = FishFamily::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'required|string|max:255|unique:fish_families,name_fr,' . $id,
            'name_en' => 'required|string|max:255|unique:fish_families,name_en,' . $id,
            'name_es' => 'required|string|max:255|unique:fish_families,name_es,' . $id,
        ]);

        $family->update($data);
        $this->broadcastChange('FishFamily', 'updated', $family->id, $family->toArray());

        return response()->json(['fish_family' => $family], 200);
    }

    /**
     * Remove the specified fish family.
     */
    public function destroy($id)
    {
        $family = FishFamily::findOrFail($id);
        $family->delete();
        $this->broadcastChange('FishFamily', 'deleted', (int)$id);

        return response()->json(['message' => 'Fish family deleted'], 200);
    }
}
