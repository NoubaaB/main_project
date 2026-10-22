<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\BroadcastsModelChanges;

class TeamController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Display a listing of teams with their chef (corporal) and workforces.
     */
    public function index(): JsonResponse
    {
        $teams = Team::with(['corporal', 'workforces'])
            ->withCount('workforces')
            ->orderBy('name')
            ->get();

        return response()->json(['teams' => $teams], 200);
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'corporal_id' => 'required|exists:corporals,id',
            'description' => 'nullable|string|max:1000',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
        ]);

        $workforceIds = $data['workforce_ids'] ?? [];
        unset($data['workforce_ids']);

        $team = Team::create($data);

        if (!empty($workforceIds)) {
            $team->workforces()->sync($workforceIds);
        }

        $team = $team->fresh()->load(['corporal', 'workforces'])->loadCount('workforces');
        $this->broadcastChange('Team', 'created', $team->id, $team->toArray());

        return response()->json(['team' => $team], 201);
    }

    /**
     * Display the specified team.
     */
    public function show($id): JsonResponse
    {
        $team = Team::with(['corporal', 'workforces'])->withCount('workforces')->findOrFail($id);
        return response()->json(['team' => $team], 200);
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'corporal_id' => 'required|exists:corporals,id',
            'description' => 'nullable|string|max:1000',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
        ]);

        $workforceIds = $data['workforce_ids'] ?? [];
        unset($data['workforce_ids']);

        $team->update($data);
        $team->workforces()->sync($workforceIds);

        $team = $team->fresh()->load(['corporal', 'workforces'])->loadCount('workforces');
        $this->broadcastChange('Team', 'updated', $team->id, $team->toArray());

        return response()->json(['team' => $team], 200);
    }

    /**
     * Remove the specified team.
     */
    public function destroy($id): JsonResponse
    {
        $team = Team::findOrFail($id);
        $teamId = $team->id;
        $team->delete();

        $this->broadcastChange('Team', 'deleted', $teamId);

        return response()->json(['message' => 'Team deleted successfully'], 200);
    }
}
