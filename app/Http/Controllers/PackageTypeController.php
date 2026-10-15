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
        $package_types = PackageType::with('components.targetPackageType')->orderBy('name_fr')->get();
        return response()->json(['package_types' => $package_types]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'name_in_invoice' => 'nullable|string|max:255',
            'size' => 'nullable|max:255',
            'description' => 'nullable|string|max:1000',
            'type_emballage' => 'nullable|string|max:255',
            'components' => 'nullable|array',
            'components.*.target_package_type_id' => 'required|exists:package_types,id',
            'components.*.multiplier' => 'nullable|numeric|min:0.0001',
        ]);

        $components = $validated['components'] ?? [];
        unset($validated['components']);

        $validated['name_en'] = $validated['name_en'] ?? $validated['name_fr'] ?? $request->input('name', '');
        $validated['name_es'] = $validated['name_es'] ?? $validated['name_fr'] ?? $request->input('name', '');
        $validated['size'] = (int)($validated['size'] ?? 0);
        $validated['name_in_invoice'] = $validated['name_in_invoice'] ?? $validated['name_fr'] ?? '';

        $packageType = PackageType::create($validated);

        if (!empty($components)) {
            foreach ($components as $comp) {
                if (!empty($comp['target_package_type_id'])) {
                    $packageType->components()->create([
                        'target_package_type_id' => $comp['target_package_type_id'],
                        'multiplier' => $comp['multiplier'] ?? 1.0000,
                    ]);
                }
            }
        }

        $packageType->load('components.targetPackageType');
        $this->broadcastChange('PackageType', 'created', $packageType->id, $packageType->toArray());

        return response()->json(['package_type' => $packageType], 201);
    }

    public function show(PackageType $packageType)
    {
        $packageType->load('components.targetPackageType');
        return response()->json(['package_type' => $packageType]);
    }

    public function update(Request $request, PackageType $packageType)
    {
        $validated = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_es' => 'nullable|string|max:255',
            'name_in_invoice' => 'nullable|string|max:255',
            'size' => 'nullable|max:255',
            'description' => 'nullable|string|max:1000',
            'type_emballage' => 'nullable|string|max:255',
            'components' => 'nullable|array',
            'components.*.target_package_type_id' => 'required|exists:package_types,id',
            'components.*.multiplier' => 'nullable|numeric|min:0.0001',
        ]);

        $hasComponents = array_key_exists('components', $validated);
        $components = $validated['components'] ?? [];
        unset($validated['components']);

        $packageType->update($validated);

        if ($hasComponents) {
            $packageType->components()->delete();
            foreach ($components as $comp) {
                if (!empty($comp['target_package_type_id'])) {
                    $packageType->components()->create([
                        'target_package_type_id' => $comp['target_package_type_id'],
                        'multiplier' => $comp['multiplier'] ?? 1.0000,
                    ]);
                }
            }
        }

        $packageType->load('components.targetPackageType');
        $this->broadcastChange('PackageType', 'updated', $packageType->id, $packageType->toArray());

        return response()->json(['package_type' => $packageType]);
    }

    public function destroy(PackageType $packageType)
    {
        $packageType->delete();
        $this->broadcastChange('PackageType', 'deleted', $packageType->id);

        return response()->json(['message' => 'Deleted']);
    }
}
