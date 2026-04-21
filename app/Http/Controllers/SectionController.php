<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
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
        ]);

        $section = Section::create($data);

        return response()->json(['section' => $section->load('corporals')], 201);
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
        ]);

        $section->update($data);

        return response()->json(['section' => $section->fresh()->load('corporals')], 200);
    }

    /**
     * Remove the specified section.
     */
    public function destroy($id): JsonResponse
    {
        $section = Section::findOrFail($id);
        $section->delete();

        return response()->json(['message' => 'Section deleted successfully'], 200);
    }
}
