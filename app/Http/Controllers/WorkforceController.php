<?php

namespace App\Http\Controllers;

use App\Models\Workforce;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkforceController extends Controller
{
    /**
     * Display a listing of workforces.
     */
    public function index(): JsonResponse
    {
        $workforces = Workforce::orderBy('full_name')->get();
        return response()->json(['workforces' => $workforces], 200);
    }

    /**
     * Store a newly created workforce.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'matricule' => 'required|string|max:100|unique:workforces,matricule',
            'nationality' => 'nullable|string|max:100',
            'sex' => 'required|in:male,female',
            'cni' => 'nullable|string|max:100',
            'is_migrant' => 'boolean',
        ]);

        $workforce = Workforce::create($data);

        return response()->json(['workforce' => $workforce], 201);
    }

    /**
     * Update the specified workforce.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $workforce = Workforce::findOrFail($id);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'matricule' => 'required|string|max:100|unique:workforces,matricule,' . $id,
            'nationality' => 'nullable|string|max:100',
            'sex' => 'required|in:male,female',
            'cni' => 'nullable|string|max:100',
            'is_migrant' => 'boolean',
        ]);

        $workforce->update($data);

        return response()->json(['workforce' => $workforce->fresh()], 200);
    }

    /**
     * Remove the specified workforce.
     */
    public function destroy($id): JsonResponse
    {
        $workforce = Workforce::findOrFail($id);
        $workforce->delete();

        return response()->json(['message' => 'Workforce deleted successfully'], 200);
    }
}
