<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserStatisticsController extends Controller
{
    /**
     * Get user activity statistics.
     * Returns: per-user daily stats, overall stats, action breakdown, module breakdown.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end'   => 'nullable|date',
            'user_id'    => 'nullable|integer',
            'module'     => 'nullable|string',
        ]);

        $dateStart    = $request->input('date_start', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateEnd      = $request->input('date_end', Carbon::today()->format('Y-m-d'));
        $userIdFilter = $request->input('user_id');
        $moduleFilter = $request->input('module');

        // 1. Get all active users with role
        $users = User::with('role:id,name')
            ->select('id', 'name', 'email', 'role_id')
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // 2. Per-user total actions
        $perUserQuery = AuditLog::select(
                'user_id',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as creates'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updates'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deletes'),
                DB::raw('MIN(created_at) as first_activity'),
                DB::raw('MAX(created_at) as last_activity')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($moduleFilter) {
            $perUserQuery->where('auditable_type', $moduleFilter);
        }

        $perUser = $perUserQuery->groupBy('user_id')->get();

        // 3. Per-user per-day actions
        $dailyPerUserQuery = AuditLog::select(
                'user_id',
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($moduleFilter) {
            $dailyPerUserQuery->where('auditable_type', $moduleFilter);
        }

        $dailyPerUser = $dailyPerUserQuery
            ->groupBy('user_id', DB::raw('DATE(created_at)'))
            ->get()
            ->groupBy('user_id');

        // 4. Module breakdown (per user)
        $modulePerUser = AuditLog::select(
                'user_id',
                'auditable_type',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->groupBy('user_id', 'auditable_type')
            ->get()
            ->groupBy('user_id');

        // 5. Global daily activity with continuous date filling
        $rawDaily = AuditLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as creates'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updates'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deletes')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($userIdFilter) {
            $rawDaily->where('user_id', $userIdFilter);
        }
        if ($moduleFilter) {
            $rawDaily->where('auditable_type', $moduleFilter);
        }

        $rawDailyCollection = $rawDaily->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get()->keyBy('date');

        $startDateObj = Carbon::parse($dateStart);
        $endDateObj   = Carbon::parse($dateEnd);
        $periodDays   = $startDateObj->diffInDays($endDateObj);

        $globalDaily = [];
        if ($periodDays <= 90) {
            $cursor = $startDateObj->copy();
            while ($cursor->lte($endDateObj)) {
                $dStr = $cursor->format('Y-m-d');
                if (isset($rawDailyCollection[$dStr])) {
                    $item = $rawDailyCollection[$dStr];
                    $globalDaily[] = [
                        'date'    => $dStr,
                        'total'   => (int) $item->total,
                        'creates' => (int) $item->creates,
                        'updates' => (int) $item->updates,
                        'deletes' => (int) $item->deletes,
                    ];
                } else {
                    $globalDaily[] = [
                        'date'    => $dStr,
                        'total'   => 0,
                        'creates' => 0,
                        'updates' => 0,
                        'deletes' => 0,
                    ];
                }
                $cursor->addDay();
            }
        } else {
            $globalDaily = $rawDailyCollection->values()->all();
        }

        // 6. Per-hour distribution (activity heatmap)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourExpr = $isSqlite ? "CAST(strftime('%H', created_at) AS INTEGER)" : "HOUR(created_at)";

        $hourlyQuery = AuditLog::select(
                DB::raw("{$hourExpr} as hour"),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($userIdFilter) {
            $hourlyQuery->where('user_id', $userIdFilter);
        }
        if ($moduleFilter) {
            $hourlyQuery->where('auditable_type', $moduleFilter);
        }

        $hourly = $hourlyQuery->groupBy(DB::raw($hourExpr))
            ->orderBy('hour')
            ->get();

        // 7. Module breakdown global
        $moduleBreakdownQuery = AuditLog::select(
                'auditable_type',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($userIdFilter) {
            $moduleBreakdownQuery->where('user_id', $userIdFilter);
        }

        $moduleBreakdown = $moduleBreakdownQuery->groupBy('auditable_type')
            ->orderByDesc('count')
            ->get();

        // 8. Recent activity feed with user context
        $recentQuery = AuditLog::with('user:id,name,email,role_id')
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']);

        if ($userIdFilter) {
            $recentQuery->where('user_id', $userIdFilter);
        }
        if ($moduleFilter) {
            $recentQuery->where('auditable_type', $moduleFilter);
        }

        $recentActivity = $recentQuery->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($a) {
                return [
                    'id'             => $a->id,
                    'user_id'        => $a->user_id,
                    'user_name'      => $a->user_name ?: ($a->user?->name ?? 'System'),
                    'user_email'     => $a->user?->email ?? '',
                    'user_role'      => $a->user?->role?->name ?? 'Operator',
                    'auditable_type' => $a->auditable_type,
                    'auditable_id'   => $a->auditable_id,
                    'action'         => $a->action,
                    'created_at'     => $a->created_at?->toISOString() ?? (string) $a->created_at,
                ];
            });

        // 9. Today's stats
        $todayStart = Carbon::today()->format('Y-m-d') . ' 00:00:00';
        $todayEnd   = Carbon::today()->format('Y-m-d') . ' 23:59:59';

        $todayStats = AuditLog::select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT user_id) as active_users'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as creates'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updates'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deletes')
            )
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->first();

        // 10. Period summary stats
        $periodSummary = [
            'total_actions' => (int) $perUser->sum('total_actions'),
            'active_users'  => $perUser->count(),
            'creates'       => (int) $perUser->sum('creates'),
            'updates'       => (int) $perUser->sum('updates'),
            'deletes'       => (int) $perUser->sum('deletes'),
        ];

        // 11. Build user stats array with 7-point sparklines
        $sparkDates = [];
        $sCursor = Carbon::parse($dateEnd)->subDays(6);
        $sEnd = Carbon::parse($dateEnd);
        while ($sCursor->lte($sEnd)) {
            $sparkDates[] = $sCursor->format('Y-m-d');
            $sCursor->addDay();
        }

        $userStats = [];
        foreach ($perUser as $row) {
            $user = $users[$row->user_id] ?? null;
            $userDailyMap = ($dailyPerUser[$row->user_id] ?? collect())->keyBy('date');

            $sparkData = [];
            foreach ($sparkDates as $spDate) {
                $sparkData[] = (int) ($userDailyMap[$spDate]->count ?? 0);
            }

            $userStats[] = [
                'user_id'        => $row->user_id,
                'user_name'      => $user->name ?? ($row->user_id ? 'User #' . $row->user_id : 'System'),
                'user_email'     => $user->email ?? '',
                'user_role'      => $user?->role?->name ?? 'Operator',
                'total_actions'  => (int) $row->total_actions,
                'creates'        => (int) $row->creates,
                'updates'        => (int) $row->updates,
                'deletes'        => (int) $row->deletes,
                'first_activity' => $row->first_activity,
                'last_activity'  => $row->last_activity,
                'sparkline'      => $sparkData,
                'daily'          => ($dailyPerUser[$row->user_id] ?? collect())->map(fn($d) => [
                    'date'  => $d->date,
                    'count' => (int) $d->count,
                ])->values(),
                'modules'        => ($modulePerUser[$row->user_id] ?? collect())->map(fn($m) => [
                    'module' => $m->auditable_type,
                    'count'  => (int) $m->count,
                ])->values(),
            ];
        }

        // Sort by total actions descending
        usort($userStats, fn($a, $b) => $b['total_actions'] - $a['total_actions']);

        return response()->json([
            'user_stats'       => $userStats,
            'global_daily'     => $globalDaily,
            'hourly'           => $hourly,
            'module_breakdown' => $moduleBreakdown,
            'recent_activity'  => $recentActivity,
            'today_stats'      => $todayStats,
            'period_summary'   => $periodSummary,
            'date_range'       => [
                'start' => $dateStart,
                'end'   => $dateEnd,
            ],
        ]);
    }
}

