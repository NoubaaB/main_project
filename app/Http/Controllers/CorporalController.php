<?php

namespace App\Http\Controllers;

use App\Models\Corporal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\BroadcastsModelChanges;

class CorporalController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * Display a listing of corporals with their sections.
     */
    public function index(): JsonResponse
    {
        $corporals = Corporal::with('sections')->orderBy('full_name')->get();
        return response()->json(['corporals' => $corporals], 200);
    }

    /**
     * Store a newly created corporal.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'sex' => 'required|in:male,female',
            'cni' => 'nullable|string|max:100',
            'is_quinzaine' => 'boolean',
            'daily_price' => 'nullable|numeric|min:0',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $corporal = Corporal::create($data);

        if (!empty($sectionIds)) {
            $corporal->sections()->sync($sectionIds);
        }

        $corporal->load('sections');
        $this->broadcastChange('Corporal', 'created', $corporal->id, $corporal->toArray());

        return response()->json(['corporal' => $corporal], 201);
    }

    /**
     * Update the specified corporal.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $corporal = Corporal::findOrFail($id);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'sex' => 'required|in:male,female',
            'cni' => 'nullable|string|max:100',
            'is_quinzaine' => 'boolean',
            'daily_price' => 'nullable|numeric|min:0',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $corporal->update($data);
        $corporal->sections()->sync($sectionIds);

        $corporal = $corporal->fresh()->load('sections');
        $this->broadcastChange('Corporal', 'updated', $corporal->id, $corporal->toArray());

        return response()->json(['corporal' => $corporal], 200);
    }

    /**
     * Remove the specified corporal.
     */
    public function destroy($id): JsonResponse
    {
        $corporal = Corporal::findOrFail($id);
        $corporalId = $corporal->id;
        $corporal->delete();
        $this->broadcastChange('Corporal', 'deleted', $corporalId);

        return response()->json(['message' => 'Corporal deleted successfully'], 200);
    }
}
