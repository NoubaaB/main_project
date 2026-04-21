<?php

namespace App\Http\Controllers;

use App\Models\Corporal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CorporalController extends Controller
{
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
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $corporal = Corporal::create($data);

        if (!empty($sectionIds)) {
            $corporal->sections()->sync($sectionIds);
        }

        return response()->json(['corporal' => $corporal->load('sections')], 201);
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
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $corporal->update($data);
        $corporal->sections()->sync($sectionIds);

        return response()->json(['corporal' => $corporal->fresh()->load('sections')], 200);
    }

    /**
     * Remove the specified corporal.
     */
    public function destroy($id): JsonResponse
    {
        $corporal = Corporal::findOrFail($id);
        $corporal->delete();

        return response()->json(['message' => 'Corporal deleted successfully'], 200);
    }
}
