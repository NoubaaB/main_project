<?php

namespace App\Http\Controllers;

use App\Models\PackageType;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class PackageTypeController extends Controller
{
    use BroadcastsModelChanges;

    public function index()
    {
        $package_types = PackageType::orderBy('name_fr')->get();
        return response()->json(['package_types' => $package_types]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type_emballage' => 'nullable|string|max:255',
        ]);

        $packageType = PackageType::create($validated);
        $this->broadcastChange('PackageType', 'created', $packageType->id);

        return response()->json(['package_type' => $packageType], 201);
    }

    public function show(PackageType $packageType)
    {
        return response()->json(['package_type' => $packageType]);
    }

    public function update(Request $request, PackageType $packageType)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type_emballage' => 'nullable|string|max:255',
        ]);

        $packageType->update($validated);
        $this->broadcastChange('PackageType', 'updated', $packageType->id);

        return response()->json(['package_type' => $packageType]);
    }

    public function destroy(PackageType $packageType)
    {
        $packageType->delete();
        $this->broadcastChange('PackageType', 'deleted', $packageType->id);

        return response()->json(['message' => 'Deleted']);
    }
}
