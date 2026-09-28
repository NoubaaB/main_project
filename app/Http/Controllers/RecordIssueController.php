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
            ->with(['reporter:id,name,email', 'resolver:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->has('record_type')) {
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

        $issue = RecordIssue::create([
            'record_type' => $data['record_type'],
            'record_id'   => $data['record_id'],
            'description' => $data['description'],
            'status'      => 'open',
            'reported_by' => $user->id,
        ]);

        $issue->load('reporter:id,name,email');

        // Notify the record creator and last updater
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
        $recordIssue->load('reporter:id,name,email', 'resolver:id,name,email');

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

        $recordIssue->load('reporter:id,name,email', 'resolver:id,name,email');

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

            // Find the user who created the record
            $creatorLog = AuditLog::forRecord($issue->record_type, $issue->record_id)
                ->where('action', 'created')
                ->first();

            // Find the last user who updated the record
            $updaterLog = AuditLog::forRecord($issue->record_type, $issue->record_id)
                ->where('action', 'updated')
                ->orderByDesc('created_at')
                ->first();

            $userIdsToNotify = collect();

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

            foreach ($userIdsToNotify as $userId) {
                $notifications[] = [
                    'user_id'        => $userId,
                    'type'           => $issue->record_type,
                    'action'         => 'issue_flagged',
                    'record_id'      => $issue->record_id,
                    'data'           => json_encode([
                        'issue_id'    => $issue->id,
                        'description' => mb_substr($issue->description, 0, 200),
                        'reporter'    => $reporter->name,
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

            Notification::create([
                'user_id'        => $issue->reported_by,
                'type'           => $issue->record_type,
                'action'         => 'issue_resolved',
                'record_id'      => $issue->record_id,
                'data'           => json_encode([
                    'issue_id'        => $issue->id,
                    'resolution_note' => mb_substr($issue->resolution_note ?? '', 0, 200),
                    'resolver'        => $resolver->name,
                ]),
                'actor_name'     => $resolver->name,
                'actor_id'       => $resolver->id,
                'permission_key' => $permissionKey,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[RecordIssueController] Failed to notify issue resolution: ' . $e->getMessage());
        }
    }
}
