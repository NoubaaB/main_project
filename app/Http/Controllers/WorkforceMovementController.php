<?php

namespace App\Http\Controllers;

use App\Models\Workforce;
use App\Models\WorkforceMovement;
use App\Models\CompanySetting;
use App\Models\FortnightActivity;
use App\Models\Corporal;
use App\Models\Section;
use App\Models\Team;
use App\Traits\BroadcastsModelChanges;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkforceMovementController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Get workforce absences evaluation based on threshold days and latest fortnight activities.
     */
    public function getAbsences(Request $request): JsonResponse
    {
        $defaultThreshold = (int) CompanySetting::getValue('workforce_absence_threshold_days', 7);
        $thresholdDays = $request->has('threshold') ? (int) $request->threshold : $defaultThreshold;
        if ($thresholdDays < 1) $thresholdDays = 7;

        $referenceDateStr = $request->query('reference_date');
        $referenceDate = $referenceDateStr ? Carbon::parse($referenceDateStr) : Carbon::today();

        // Load all workforces with their teams and latest activity
        $workforces = Workforce::with([
            'teams:id,name,corporal_id',
            'movements' => function ($q) {
                $q->orderBy('start_date', 'desc')->orderBy('id', 'desc');
            }
        ])->orderBy('full_name')->get();

        // Get the latest fortnight activity per workforce in bulk for optimal performance
        $latestActivities = FortnightActivity::select('workforce_id', DB::raw('MAX(date) as latest_date'))
            ->groupBy('workforce_id')
            ->pluck('latest_date', 'workforce_id');

        $result = [];
        $activeCount = 0;
        $absentCount = 0;
        $leftCompanyCount = 0;
        $totalAbsenceDaysSum = 0;
        $totalIncidentsCount = 0;

        foreach ($workforces as $wf) {
            $latestDate = $latestActivities[$wf->id] ?? null;

            // Days since last activity
            if ($latestDate) {
                $daysSinceLastActivity = Carbon::parse($latestDate)->diffInDays($referenceDate, false);
            } else {
                // If never had an activity, calculate from creation date
                $daysSinceLastActivity = $wf->created_at ? Carbon::parse($wf->created_at)->diffInDays($referenceDate, false) : 999;
            }

            // Determine effective status
            $currentStatus = $wf->status ?: 'active';
            $isAbsentByThreshold = false;

            if ($currentStatus === 'left_company') {
                $leftCompanyCount++;
            } else {
                if ($daysSinceLastActivity >= $thresholdDays) {
                    $currentStatus = 'absent';
                    $isAbsentByThreshold = true;
                    $absentCount++;
                } else {
                    $currentStatus = 'active';
                    $activeCount++;
                }
            }

            // Calculate absence stats from historical movements
            $absenceMovements = $wf->movements->where('type', 'absence');
            $absenceCount = $absenceMovements->count();
            $totalAbsenceDays = (int) $absenceMovements->sum('days_count');

            // If currently absent, add current inactive days to metrics if no open movement
            if ($currentStatus === 'absent' && $daysSinceLastActivity > 0) {
                $effectiveAbsentDays = max($totalAbsenceDays, (int) $daysSinceLastActivity);
            } else {
                $effectiveAbsentDays = $totalAbsenceDays;
            }

            $totalAbsenceDaysSum += $effectiveAbsentDays;
            $totalIncidentsCount += $absenceCount;

            // Latest motif / movement
            $latestMovement = $wf->movements->first();
            $latestAbsenceMovement = $absenceMovements->first();

            $result[] = [
                'id' => $wf->id,
                'full_name' => $wf->full_name,
                'matricule' => $wf->matricule,
                'cni' => $wf->cni,
                'sex' => $wf->sex,
                'nationality' => $wf->nationality,
                'status' => $currentStatus,
                'db_status' => $wf->status,
                'is_absent_by_threshold' => $isAbsentByThreshold,
                'last_activity_date' => $latestDate,
                'days_since_last_activity' => max(0, (int) $daysSinceLastActivity),
                'departure_date' => $wf->departure_date ? $wf->departure_date->format('Y-m-d') : null,
                'departure_reason' => $wf->departure_reason,
                'return_date' => $wf->return_date ? $wf->return_date->format('Y-m-d') : null,
                'previous_team_ids' => $wf->previous_team_ids ?: [],
                'teams' => $wf->teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'corporal_id' => $t->corporal_id]),
                'absence_count' => $absenceCount,
                'total_absence_days' => $effectiveAbsentDays,
                'latest_motif' => $latestAbsenceMovement ? $latestAbsenceMovement->reason : null,
                'latest_movement' => $latestMovement ? [
                    'id' => $latestMovement->id,
                    'type' => $latestMovement->type,
                    'start_date' => $latestMovement->start_date ? $latestMovement->start_date->format('Y-m-d') : null,
                    'end_date' => $latestMovement->end_date ? $latestMovement->end_date->format('Y-m-d') : null,
                    'days_count' => $latestMovement->days_count,
                    'reason' => $latestMovement->reason,
                    'notes' => $latestMovement->notes,
                    'restored_teams' => $latestMovement->restored_teams,
                ] : null,
            ];
        }

        return response()->json([
            'threshold_days' => $thresholdDays,
            'reference_date' => $referenceDate->format('Y-m-d'),
            'summary' => [
                'total_workforces' => count($workforces),
                'active_count' => $activeCount,
                'absent_count' => $absentCount,
                'left_company_count' => $leftCompanyCount,
                'total_absence_incidents' => $totalIncidentsCount,
                'total_absence_days' => $totalAbsenceDaysSum,
            ],
            'workforces' => $result,
        ]);
    }

    /**
     * Get the absence threshold setting.
     */
    public function getSetting(): JsonResponse
    {
        $threshold = (int) CompanySetting::getValue('workforce_absence_threshold_days', 7);
        return response()->json(['threshold_days' => $threshold ?: 7]);
    }

    /**
     * Update the absence threshold setting.
     */
    public function saveSetting(Request $request): JsonResponse
    {
        $data = $request->validate([
            'threshold_days' => 'required|integer|min:1|max:365',
        ]);

        CompanySetting::setValue('workforce_absence_threshold_days', (string) $data['threshold_days']);

        $this->broadcastChange('CompanySetting', 'updated', 0, [
            'key' => 'workforce_absence_threshold_days',
            'value' => (string) $data['threshold_days'],
        ]);

        return response()->json([
            'message' => 'Absence threshold saved successfully',
            'threshold_days' => $data['threshold_days'],
        ]);
    }

    /**
     * Record an absence motif for a workforce.
     */
    public function recordMotif(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'days_count' => 'nullable|integer|min:1',
            'is_ongoing' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workforce = Workforce::findOrFail($id);

        $isOngoing = !empty($data['is_ongoing']);
        $startDate = Carbon::parse($data['start_date']);
        $daysCount = $data['days_count'] ?? null;
        if (!$daysCount) {
            $endDate = (!$isOngoing && !empty($data['end_date'])) ? Carbon::parse($data['end_date']) : Carbon::today();
            $daysCount = max(1, $startDate->diffInDays($endDate) + 1);
        }

        $movement = WorkforceMovement::create([
            'workforce_id' => $workforce->id,
            'type' => 'absence',
            'start_date' => $data['start_date'],
            'end_date' => $isOngoing ? null : ($data['end_date'] ?? null),
            'days_count' => $daysCount,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'metadata' => [
                'is_ongoing' => $isOngoing,
            ],
            'created_by' => Auth::id(),
        ]);

        if ($workforce->status !== 'left_company') {
            $workforce->status = 'absent';
            $workforce->save();
        }

        $this->broadcastChange('WorkforceMovement', 'created', $movement->id, $movement->toArray());
        $this->broadcastChange('Workforce', 'updated', $workforce->id, $workforce->toArray());

        return response()->json([
            'message' => 'Absence motif recorded successfully',
            'movement' => $movement,
            'workforce' => $workforce,
        ], 201);
    }

    /**
     * Mark workforce as left the company.
     * Removes workforce from all associated teams and archives prior team IDs.
     */
    public function leaveCompany(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'departure_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workforce = Workforce::with('teams')->findOrFail($id);

        // Capture previous team associations
        $teamIds = $workforce->teams->pluck('id')->toArray();

        // Detach from all teams
        $workforce->teams()->detach();

        // Update workforce status
        $workforce->status = 'left_company';
        $workforce->departure_date = $data['departure_date'];
        $workforce->departure_reason = $data['reason'];
        $workforce->previous_team_ids = $teamIds;
        $workforce->save();

        // Record movement
        $movement = WorkforceMovement::create([
            'workforce_id' => $workforce->id,
            'type' => 'departure',
            'start_date' => $data['departure_date'],
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'previous_team_ids' => $teamIds,
            'created_by' => Auth::id(),
        ]);

        $this->broadcastChange('Workforce', 'updated', $workforce->id, $workforce->toArray());
        $this->broadcastChange('WorkforceMovement', 'created', $movement->id, $movement->toArray());

        return response()->json([
            'message' => 'Workforce marked as departed and detached from all teams',
            'workforce' => $workforce,
            'movement' => $movement,
        ]);
    }

    /**
     * Mark workforce as back to work.
     * Optionally restores previous team associations and records the return motif.
     */
    public function returnToWork(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'restore_teams' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workforce = Workforce::findOrFail($id);

        $restoreTeams = $request->boolean('restore_teams', true);
        $teamsRestored = false;

        if ($restoreTeams && !empty($workforce->previous_team_ids)) {
            $workforce->teams()->syncWithoutDetaching($workforce->previous_team_ids);
            $teamsRestored = true;
        }

        // Close any ongoing absence movement if open
        WorkforceMovement::where('workforce_id', $workforce->id)
            ->where('type', 'absence')
            ->whereNull('end_date')
            ->update([
                'end_date' => $data['return_date'],
            ]);

        $workforce->status = 'active';
        $workforce->return_date = $data['return_date'];
        $workforce->save();

        // Record return movement
        $movement = WorkforceMovement::create([
            'workforce_id' => $workforce->id,
            'type' => 'return',
            'start_date' => $data['return_date'],
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'restored_teams' => $teamsRestored,
            'previous_team_ids' => $workforce->previous_team_ids,
            'created_by' => Auth::id(),
        ]);

        $workforce->load('teams:id,name,corporal_id');

        $this->broadcastChange('Workforce', 'updated', $workforce->id, $workforce->toArray());
        $this->broadcastChange('WorkforceMovement', 'created', $movement->id, $movement->toArray());

        return response()->json([
            'message' => 'Workforce returned to work successfully',
            'workforce' => $workforce,
            'movement' => $movement,
            'teams_restored' => $teamsRestored,
        ]);
    }

    /**
     * Get complete movement history for a workforce.
     */
    public function getHistory($id): JsonResponse
    {
        $workforce = Workforce::with([
            'teams:id,name,corporal_id',
            'movements' => function ($q) {
                $q->orderBy('start_date', 'desc')->orderBy('id', 'desc')->with('creator:id,name');
            }
        ])->findOrFail($id);

        // Fetch team names for previous_team_ids referenced in movements
        $allReferencedTeamIds = [];
        foreach ($workforce->movements as $m) {
            if (!empty($m->previous_team_ids) && is_array($m->previous_team_ids)) {
                $allReferencedTeamIds = array_merge($allReferencedTeamIds, $m->previous_team_ids);
            }
        }
        $teamsMap = Team::whereIn('id', array_unique($allReferencedTeamIds))->pluck('name', 'id')->toArray();

        $movements = $workforce->movements->map(function ($m) use ($teamsMap) {
            $teamNames = [];
            if (!empty($m->previous_team_ids) && is_array($m->previous_team_ids)) {
                foreach ($m->previous_team_ids as $tid) {
                    if (isset($teamsMap[$tid])) {
                        $teamNames[] = $teamsMap[$tid];
                    }
                }
            }

            return [
                'id' => $m->id,
                'type' => $m->type,
                'start_date' => $m->start_date ? $m->start_date->format('Y-m-d') : null,
                'end_date' => $m->end_date ? $m->end_date->format('Y-m-d') : null,
                'days_count' => $m->days_count,
                'reason' => $m->reason,
                'notes' => $m->notes,
                'restored_teams' => $m->restored_teams,
                'previous_team_names' => $teamNames,
                'created_by_name' => $m->creator ? $m->creator->name : 'System',
                'created_at' => $m->created_at ? $m->created_at->format('Y-m-d H:i') : null,
            ];
        });

        return response()->json([
            'workforce' => [
                'id' => $workforce->id,
                'full_name' => $workforce->full_name,
                'matricule' => $workforce->matricule,
                'status' => $workforce->status,
                'teams' => $workforce->teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
            ],
            'movements' => $movements,
        ]);
    }

    /**
     * Get workforce movement tracking for Workforce Statistics during a selected date range.
     * Tracks all corporals, teams, and sections worked with, and counts sections and corporals.
     * Supports daily, monthly, and yearly granularity.
     */
    public function getStatisticsMovements(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $granularity = strtolower($request->query('granularity', 'daily')); // daily, monthly, yearly
        if (!in_array($granularity, ['daily', 'monthly', 'yearly'])) {
            $granularity = 'daily';
        }

        $workforceIds = $request->query('workforce_ids');
        if (is_string($workforceIds)) {
            $workforceIds = array_filter(array_map('intval', explode(',', $workforceIds)));
        }

        if (!$startDate || !$endDate) {
            $startDate = Carbon::today()->startOfMonth()->toDateString();
            $endDate = Carbon::today()->endOfMonth()->toDateString();
        }

        // Query fortnight activities within range
        $query = FortnightActivity::with([
            'workforce:id,full_name,matricule,cni',
            'corporal:id,full_name,cni',
            'section:id,name',
        ])
        ->whereBetween('date', [$startDate, $endDate])
        ->orderBy('date', 'desc');

        if (!empty($workforceIds)) {
            $query->whereIn('workforce_id', $workforceIds);
        }

        $activities = $query->get();

        // Also fetch teams mapped by corporal or team_workforce
        $teams = Team::with('workforces:id')->get();
        $corporalToTeamMap = [];
        $workforceToTeamsMap = [];

        foreach ($teams as $t) {
            if ($t->corporal_id) {
                $corporalToTeamMap[$t->corporal_id][] = $t->name;
            }
            foreach ($t->workforces as $w) {
                $workforceToTeamsMap[$w->id][] = $t->name;
            }
        }

        // Aggregate statistics per workforce
        $workforceStats = [];
        $allCorporals = [];
        $allSections = [];
        $allTeams = [];
        $timelineRecords = [];

        foreach ($activities as $act) {
            $wfId = $act->workforce_id;
            $wfName = $act->workforce ? $act->workforce->full_name : "ID #{$wfId}";
            $corpId = $act->corporal_id;
            $corpName = $act->corporal ? $act->corporal->full_name : '-';
            $secId = $act->section_id;
            $secName = $act->section ? $act->section->name : '-';
            $date = Carbon::parse($act->date);
            $amount = (float) $act->price;

            // Associate team name(s)
            $teamNames = [];
            if ($corpId && isset($corporalToTeamMap[$corpId])) {
                $teamNames = array_merge($teamNames, $corporalToTeamMap[$corpId]);
            }
            if (isset($workforceToTeamsMap[$wfId])) {
                $teamNames = array_merge($teamNames, $workforceToTeamsMap[$wfId]);
            }
            $teamNames = array_values(array_unique($teamNames));
            $teamLabel = !empty($teamNames) ? implode(', ', $teamNames) : '-';

            // Global unique sets
            if ($corpId && $corpName !== '-') $allCorporals[$corpId] = $corpName;
            if ($secId && $secName !== '-') $allSections[$secId] = $secName;
            foreach ($teamNames as $tn) { $allTeams[$tn] = true; }

            // Per-workforce accumulation
            if (!isset($workforceStats[$wfId])) {
                $workforceStats[$wfId] = [
                    'id' => $wfId,
                    'full_name' => $wfName,
                    'matricule' => $act->workforce?->matricule,
                    'cni' => $act->workforce?->cni,
                    'corporals' => [],
                    'sections' => [],
                    'teams' => [],
                    'activities_count' => 0,
                    'total_amount' => 0,
                    'dates' => [],
                ];
            }

            if ($corpId && $corpName !== '-') $workforceStats[$wfId]['corporals'][$corpId] = $corpName;
            if ($secId && $secName !== '-') $workforceStats[$wfId]['sections'][$secId] = $secName;
            foreach ($teamNames as $tn) { $workforceStats[$wfId]['teams'][$tn] = true; }
            $workforceStats[$wfId]['activities_count']++;
            $workforceStats[$wfId]['total_amount'] += $amount;
            $workforceStats[$wfId]['dates'][$act->date->format('Y-m-d')] = true;

            // Bucket by granularity
            if ($granularity === 'yearly') {
                $periodKey = $date->format('Y');
                $periodLabel = $date->format('Y');
            } elseif ($granularity === 'monthly') {
                $periodKey = $date->format('Y-m');
                $periodLabel = $date->translatedFormat('F Y');
            } else {
                $periodKey = $date->format('Y-m-d');
                $periodLabel = $date->format('Y-m-d');
            }

            $bucketKey = "{$wfId}_{$periodKey}_{$corpId}_{$secId}";

            if (!isset($timelineRecords[$bucketKey])) {
                $timelineRecords[$bucketKey] = [
                    'workforce_id' => $wfId,
                    'workforce_name' => $wfName,
                    'matricule' => $act->workforce?->matricule,
                    'period_key' => $periodKey,
                    'period_label' => $periodLabel,
                    'corporal_id' => $corpId,
                    'corporal_name' => $corpName,
                    'section_id' => $secId,
                    'section_name' => $secName,
                    'team_names' => $teamNames,
                    'team_label' => $teamLabel,
                    'activities_count' => 0,
                    'days_count' => 0,
                    'total_amount' => 0,
                    'dates' => [],
                ];
            }

            $timelineRecords[$bucketKey]['activities_count']++;
            $timelineRecords[$bucketKey]['total_amount'] += $amount;
            $timelineRecords[$bucketKey]['dates'][$act->date->format('Y-m-d')] = true;
        }

        // Format per-workforce summary
        $workforceSummaryList = array_values(array_map(function ($wf) {
            return [
                'id' => $wf['id'],
                'full_name' => $wf['full_name'],
                'matricule' => $wf['matricule'],
                'cni' => $wf['cni'],
                'corporals_count' => count($wf['corporals']),
                'corporals_list' => array_values($wf['corporals']),
                'sections_count' => count($wf['sections']),
                'sections_list' => array_values($wf['sections']),
                'teams_count' => count($wf['teams']),
                'teams_list' => array_keys($wf['teams']),
                'work_days_count' => count($wf['dates']),
                'activities_count' => $wf['activities_count'],
                'total_amount' => round($wf['total_amount'], 2),
            ];
        }, $workforceStats));

        // Format timeline records
        $formattedTimeline = array_values(array_map(function ($row) {
            $row['days_count'] = count($row['dates']);
            $row['total_amount'] = round($row['total_amount'], 2);
            unset($row['dates']);
            return $row;
        }, $timelineRecords));

        // Sort timeline
        usort($formattedTimeline, function ($a, $b) {
            return strcmp($b['period_key'], $a['period_key']);
        });

        return response()->json([
            'granularity' => $granularity,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => [
                'total_activities' => $activities->count(),
                'total_amount' => round($activities->sum('price'), 2),
                'distinct_workforces_count' => count($workforceStats),
                'distinct_corporals_count' => count($allCorporals),
                'distinct_sections_count' => count($allSections),
                'distinct_teams_count' => count($allTeams),
                'all_corporals' => array_values($allCorporals),
                'all_sections' => array_values($allSections),
                'all_teams' => array_keys($allTeams),
            ],
            'workforce_summaries' => $workforceSummaryList,
            'timeline' => $formattedTimeline,
        ]);
    }
}
