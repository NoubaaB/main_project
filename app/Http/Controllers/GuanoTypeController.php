<?php

namespace App\Http\Controllers;

use App\Models\GuanoType;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class GuanoTypeController extends Controller
{
    use BroadcastsModelChanges;

    public function index()
    {
        $guano_types = GuanoType::orderBy('name_fr')->get();
        return response()->json(['guano_types' => $guano_types]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'name_in_invoice' => 'nullable|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['name_en'] = $validated['name_en'] ?? $validated['name_fr'] ?? $request->input('name', '');
        $validated['name_es'] = $validated['name_es'] ?? $validated['name_fr'] ?? $request->input('name', '');
        $validated['name_in_invoice'] = $validated['name_in_invoice'] ?? $validated['name_fr'] ?? '';

        $guanoType = GuanoType::create($validated);
        $this->broadcastChange('GuanoType', 'created', $guanoType->id);

        return response()->json(['guano_type' => $guanoType], 201);
    }

    public function show(GuanoType $guanoType)
    {
        return response()->json(['guano_type' => $guanoType]);
    }

    public function update(Request $request, GuanoType $guanoType)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'name_in_invoice' => 'nullable|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $guanoType->update($validated);
        $this->broadcastChange('GuanoType', 'updated', $guanoType->id);

        return response()->json(['guano_type' => $guanoType]);
    }

    public function destroy(GuanoType $guanoType)
    {
        $guanoType->delete();
        $this->broadcastChange('GuanoType', 'deleted', $guanoType->id);

        return response()->json(['message' => 'Deleted']);
    }
}
