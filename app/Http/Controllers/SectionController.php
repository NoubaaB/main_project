<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\BroadcastsModelChanges;

class SectionController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * Display a listing of sections with their corporals.
     */
    public function index(): JsonResponse
    {
        $sections = Section::with('corporals')->orderBy('name')->get();
        return response()->json(['sections' => $sections], 200);
    }

    /**
     * Store a newly created section.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'daily_price' => 'required|numeric|min:0',
            'is_price_modifiable' => 'boolean',
            'corporal_ids' => 'nullable|array',
            'corporal_ids.*' => 'exists:corporals,id',
        ]);

        $corporalIds = $data['corporal_ids'] ?? null;
        unset($data['corporal_ids']);

        $section = Section::create($data);

        if ($corporalIds !== null) {
            $section->corporals()->sync($corporalIds);
        }

        $section->load('corporals');
        $this->broadcastChange('Section', 'created', $section->id, $section->toArray());

        return response()->json(['section' => $section], 201);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $section = Section::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'daily_price' => 'required|numeric|min:0',
            'is_price_modifiable' => 'boolean',
            'corporal_ids' => 'nullable|array',
            'corporal_ids.*' => 'exists:corporals,id',
        ]);

        $corporalIds = $data['corporal_ids'] ?? null;
        unset($data['corporal_ids']);

        $section->update($data);

        if ($corporalIds !== null) {
            $section->corporals()->sync($corporalIds);
        }

        $section = $section->fresh()->load('corporals');
        $this->broadcastChange('Section', 'updated', $section->id, $section->toArray());

        return response()->json(['section' => $section], 200);
    }

    /**
     * Remove the specified section.
     */
    public function destroy($id): JsonResponse
    {
        $section = Section::findOrFail($id);
        $sectionId = $section->id;
        $section->delete();
        $this->broadcastChange('Section', 'deleted', $sectionId);

        return response()->json(['message' => 'Section deleted successfully'], 200);
    }
}
