<?php

namespace App\Http\Controllers;

use App\Models\RecordIssue;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordIssueController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List issues for a specific record.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'record_type' => 'required|string|in:Charge,Production,Shipping',
            'record_id'   => 'required|integer',
        ]);

        $issues = RecordIssue::forRecord($data['record_type'], $data['record_id'])
            ->with(['reporter:id,name,email', 'resolver:id,name,email'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['issues' => $issues]);
    }

    /**
     * List all open issues (for dashboard / admin overview).
     */
    public function openIssues(Request $request)
    {
        $query = RecordIssue::open()
            ->with(['reporter:id,name,email', 'resolver:id,name,email', 'bonCreator:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->has('record_type')) {
            $query->where('record_type', $request->record_type);
        }

        $issues = $query->paginate($request->input('per_page', 50));

        return response()->json($issues);
    }

    /**
     * Get unresolved issues summary for the authenticated user and general count.
     * Used on app access to display the unresolved issues systembar button and banner.
     */
    public function unresolvedSummary(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        $baseQuery = RecordIssue::open();

        $byModule = [
            'Charge'     => (clone $baseQuery)->where('record_type', 'Charge')->count(),
            'Production' => (clone $baseQuery)->where('record_type', 'Production')->count(),
            'Shipping'   => (clone $baseQuery)->where('record_type', 'Shipping')->count(),
        ];

        $myBonsQuery = RecordIssue::open()->where(function ($q) use ($user) {
            $q->where('bon_creator_id', $user->id)
              ->orWhereIn('record_id', function ($sub) use ($user) {
                  $sub->select('auditable_id')
                      ->from('audit_logs')
                      ->where('user_id', $user->id)
                      ->where('action', 'created');
              });
        });

        $myBonsCount = $myBonsQuery->count();
        $reportedCount = RecordIssue::open()->where('reported_by', $user->id)->count();
        $totalOpen = (clone $baseQuery)->count();

        // Effective count for this user: if admin, show all open or my bons; if regular user, show my bons count
        $activeCount = $isSuperAdmin ? $totalOpen : ($myBonsCount > 0 ? $myBonsCount : $reportedCount);

        // Fetch top recent unresolved issues for preview in systembar dropdown
        $recentIssues = RecordIssue::open()
            ->with(['reporter:id,name,email', 'bonCreator:id,name,email'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json([
            'count'            => $activeCount,
            'my_bons_count'    => $myBonsCount,
            'reported_count'   => $reportedCount,
            'total_open'       => $totalOpen,
            'by_module'        => $byModule,
            'is_super_admin'   => $isSuperAdmin,
            'recent_issues'    => $recentIssues,
        ]);
    }

    /**
     * Get all unresolved issues with filtering by scope and module.
     */
    public function unresolved(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        $query = RecordIssue::open()
            ->with(['reporter:id,name,email', 'resolver:id,name,email', 'bonCreator:id,name,email'])
            ->orderByDesc('created_at');

        $scope = $request->input('scope', $isSuperAdmin ? 'all' : 'my_bons');

        if ($scope === 'my_bons') {
            $query->where(function ($q) use ($user) {
                $q->where('bon_creator_id', $user->id)
                  ->orWhereIn('record_id', function ($sub) use ($user) {
                      $sub->select('auditable_id')
                          ->from('audit_logs')
                          ->where('user_id', $user->id)
                          ->where('action', 'created');
                  });
            });
        } elseif ($scope === 'reported_by_me') {
            $query->where('reported_by', $user->id);
        }

        if ($request->filled('record_type') && in_array($request->record_type, ['Charge', 'Production', 'Shipping'])) {
            $query->where('record_type', $request->record_type);
        }

        $issues = $query->paginate($request->input('per_page', 50));

        return response()->json($issues);
    }

    /**
     * Count open issues per record type.
     */
    public function openCounts()
    {
        $counts = RecordIssue::open()
            ->selectRaw('record_type, count(*) as count')
            ->groupBy('record_type')
            ->pluck('count', 'record_type');

        return response()->json(['counts' => $counts]);
    }

    /**
     * Batch counts: return open issue counts per record_id for a given type.
     * Used by list views to show issue badges without modifying existing controllers.
     */
    public function batchCounts(Request $request)
    {
        $data = $request->validate([
            'record_type' => 'required|string|in:Charge,Production,Shipping',
            'record_ids'  => 'required|array',
            'record_ids.*' => 'integer',
        ]);

        $counts = RecordIssue::open()
            ->where('record_type', $data['record_type'])
            ->whereIn('record_id', $data['record_ids'])
            ->selectRaw('record_id, count(*) as count')
            ->groupBy('record_id')
            ->pluck('count', 'record_id');

        return response()->json(['counts' => $counts]);
    }

    /**
     * Create a new issue and notify the record creator + last updater.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'record_type' => 'required|string|in:Charge,Production,Shipping',
            'record_id'   => 'required|integer',
            'description' => 'required|string|max:2000',
        ]);

        // Verify the record actually exists
        $modelClass = RecordIssue::modelClassForType($data['record_type']);
        if (!$modelClass || !$modelClass::find($data['record_id'])) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $user = Auth::user();

        // Resolve human-readable bon info, date, and creator id
        $bonInfo = RecordIssue::resolveBonInfo($data['record_type'], $data['record_id']);

        $issue = RecordIssue::create([
            'record_type'    => $data['record_type'],
            'record_id'      => $data['record_id'],
            'bon_reference'  => $bonInfo['reference'] ?? null,
            'bon_date'       => $bonInfo['date'] ?? null,
            'bon_creator_id' => $bonInfo['creator_id'] ?? null,
            'description'    => $data['description'],
            'status'         => 'open',
            'reported_by'    => $user->id,
        ]);

        $issue->load(['reporter:id,name,email', 'bonCreator:id,name,email']);

        // Notify the record creator (User B) and last updater
        $this->notifyRecordOwners($issue, $user);

        // Broadcast to all clients via WebSocket
        $this->broadcastChange('RecordIssue', 'created', $issue->id, $issue->toArray());

        return response()->json(['issue' => $issue], 201);
    }

    /**
     * Update issue status (e.g. mark as in_progress).
     */
    public function update(Request $request, RecordIssue $recordIssue)
    {
        $data = $request->validate([
            'status'      => 'sometimes|string|in:open,in_progress',
            'description' => 'sometimes|string|max:2000',
        ]);

        $recordIssue->update($data);
        $recordIssue->load(['reporter:id,name,email', 'resolver:id,name,email', 'bonCreator:id,name,email']);

        $this->broadcastChange('RecordIssue', 'updated', $recordIssue->id, $recordIssue->toArray());

        return response()->json(['issue' => $recordIssue]);
    }

    /**
     * Resolve an issue.
     */
    public function resolve(Request $request, RecordIssue $recordIssue)
    {
        $data = $request->validate([
            'resolution_note' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();

        $recordIssue->update([
            'status'          => 'resolved',
            'resolution_note' => $data['resolution_note'] ?? null,
            'resolved_by'     => $user->id,
            'resolved_at'     => now(),
        ]);

        $recordIssue->load(['reporter:id,name,email', 'resolver:id,name,email', 'bonCreator:id,name,email']);

        // Notify the reporter that their issue was resolved
        $this->notifyIssueResolved($recordIssue, $user);

        $this->broadcastChange('RecordIssue', 'updated', $recordIssue->id, $recordIssue->toArray());

        return response()->json(['issue' => $recordIssue]);
    }

    /**
     * Delete an issue (only reporter or super admin).
     */
    public function destroy(RecordIssue $recordIssue)
    {
        $user = Auth::user();

        if ($recordIssue->reported_by !== $user->id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $id = $recordIssue->id;
        $recordIssue->delete();

        $this->broadcastChange('RecordIssue', 'deleted', $id);

        return response()->json(['message' => 'Issue deleted']);
    }

    /**
     * Notify the record creator and last updater about the new issue.
     * Finds them from the audit_logs table.
     */
    private function notifyRecordOwners(RecordIssue $issue, $reporter): void
    {
        try {
            $permissionKey = RecordIssue::permissionKeyForType($issue->record_type);
            if (!$permissionKey) return;

            $userIdsToNotify = collect();

            // 1. Direct bon creator id if already resolved
            if ($issue->bon_creator_id) {
                $userIdsToNotify->push($issue->bon_creator_id);
            }

            // 2. Find the user who created the record via audit_logs
            $creatorLog = AuditLog::forRecord($issue->record_type, $issue->record_id)
                ->where('action', 'created')
                ->first();

            // 3. Find the last user who updated the record
            $updaterLog = AuditLog::forRecord($issue->record_type, $issue->record_id)
                ->where('action', 'updated')
                ->orderByDesc('created_at')
                ->first();

            if ($creatorLog && $creatorLog->user_id) {
                $userIdsToNotify->push($creatorLog->user_id);
            }
            if ($updaterLog && $updaterLog->user_id) {
                $userIdsToNotify->push($updaterLog->user_id);
            }

            // Remove duplicates and exclude the reporter themselves
            $userIdsToNotify = $userIdsToNotify->unique()->filter(fn($id) => $id !== $reporter->id);

            if ($userIdsToNotify->isEmpty()) {
                // If no creator/updater found, notify all users with permission (except reporter)
                $userIdsToNotify = User::where('is_active', true)
                    ->where('id', '!=', $reporter->id)
                    ->with('role')
                    ->get()
                    ->filter(fn($u) => $u->hasPermission($permissionKey))
                    ->pluck('id');
            }

            if ($userIdsToNotify->isEmpty()) return;

            $now = now();
            $notifications = [];

            $bonRef = $issue->bon_reference ?: "{$issue->record_type} #{$issue->record_id}";

            foreach ($userIdsToNotify as $userId) {
                $notifications[] = [
                    'user_id'        => $userId,
                    'type'           => $issue->record_type,
                    'action'         => 'issue_flagged',
                    'record_id'      => $issue->record_id,
                    'data'           => json_encode([
                        'issue_id'       => $issue->id,
                        'bon_reference'  => $bonRef,
                        'bon_creator_id' => $issue->bon_creator_id,
                        'description'    => mb_substr($issue->description, 0, 200),
                        'reporter'       => $reporter->name,
                        'reporter_id'    => $reporter->id,
                    ]),
                    'actor_name'     => $reporter->name,
                    'actor_id'       => $reporter->id,
                    'permission_key' => $permissionKey,
                    'read_at'        => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (!empty($notifications)) {
                Notification::insert($notifications);

                try {
                    $eligibleUserIds = array_column($notifications, 'user_id');
                    if (!empty($eligibleUserIds)) {
                        $webPush = app(\App\Services\WebPushService::class);
                        $payload = [
                            'title' => "Jupiter ERP • Problème signalé ({$issue->record_type})",
                            'body'  => "{$reporter->name} a signalé un problème sur {$bonRef}",
                            'icon'  => '/images/jupiter-logo.png',
                            'badge' => '/favicon.ico',
                            'tag'   => "jupiter-issue-{$issue->id}",
                            'data'  => [
                                'model'     => $issue->record_type,
                                'action'    => 'issue_reported',
                                'record_id' => $issue->record_id,
                                'url'       => '/' . strtolower($issue->record_type) . 's/' . $issue->record_id,
                            ],
                        ];
                        $webPush->sendToUsers($eligibleUserIds, $payload);
                    }
                } catch (\Throwable $pe) {
                    \Log::warning('[RecordIssueController] WebPush dispatch failed: ' . $pe->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('[RecordIssueController] Failed to notify record owners: ' . $e->getMessage());
        }
    }

    /**
     * Notify the reporter that their issue was resolved.
     */
    private function notifyIssueResolved(RecordIssue $issue, $resolver): void
    {
        try {
            if ($issue->reported_by === $resolver->id) return; // Don't notify yourself

            $permissionKey = RecordIssue::permissionKeyForType($issue->record_type);
            $bonRef = $issue->bon_reference ?: "{$issue->record_type} #{$issue->record_id}";

            Notification::create([
                'user_id'        => $issue->reported_by,
                'type'           => $issue->record_type,
                'action'         => 'issue_resolved',
                'record_id'      => $issue->record_id,
                'data'           => json_encode([
                    'issue_id'        => $issue->id,
                    'bon_reference'   => $bonRef,
                    'resolution_note' => mb_substr($issue->resolution_note ?? '', 0, 200),
                    'resolver'        => $resolver->name,
                    'resolver_id'     => $resolver->id,
                ]),
                'actor_name'     => $resolver->name,
                'actor_id'       => $resolver->id,
                'permission_key' => $permissionKey,
            ]);

            try {
                $webPush = app(\App\Services\WebPushService::class);
                $payload = [
                    'title' => "Jupiter ERP • Problème résolu ({$issue->record_type})",
                    'body'  => "{$resolver->name} a marqué le problème sur {$bonRef} comme résolu.",
                    'icon'  => '/images/jupiter-logo.png',
                    'badge' => '/favicon.ico',
                    'tag'   => "jupiter-issue-resolved-{$issue->id}",
                    'data'  => [
                        'model'     => $issue->record_type,
                        'action'    => 'issue_resolved',
                        'record_id' => $issue->record_id,
                        'url'       => '/' . strtolower($issue->record_type) . 's/' . $issue->record_id,
                    ],
                ];
                $webPush->sendToUser($issue->reported_by, $payload);
            } catch (\Throwable $pe) {
                \Log::warning('[RecordIssueController] WebPush dispatch failed: ' . $pe->getMessage());
            }
        } catch (\Throwable $e) {
            \Log::warning('[RecordIssueController] Failed to notify issue resolution: ' . $e->getMessage());
        }
    }
}
