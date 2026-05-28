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
        ]);

        $dateStart = $request->input('date_start', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateEnd   = $request->input('date_end', Carbon::today()->format('Y-m-d'));

        // 1. Get all users (active)
        $users = User::select('id', 'name', 'email')
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // 2. Per-user total actions
        $perUser = AuditLog::select(
                'user_id',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as creates'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updates'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deletes'),
                DB::raw('MIN(created_at) as first_activity'),
                DB::raw('MAX(created_at) as last_activity')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->groupBy('user_id')
            ->get();

        // 3. Per-user per-day actions (for sparkline / heatmap)
        $dailyPerUser = AuditLog::select(
                'user_id',
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
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

        // 5. Global daily activity (for the main chart)
        $globalDaily = AuditLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as creates'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updates'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deletes')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // 6. Per-hour distribution (activity heatmap)
        $hourly = AuditLog::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        // 7. Module breakdown global
        $moduleBreakdown = AuditLog::select(
                'auditable_type',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->groupBy('auditable_type')
            ->orderByDesc('count')
            ->get();

        // 8. Recent activity feed (last 50 actions in range)
        $recentActivity = AuditLog::select('id', 'user_id', 'user_name', 'auditable_type', 'auditable_id', 'action', 'created_at')
            ->whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

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

        // Build user stats array
        $userStats = [];
        foreach ($perUser as $row) {
            $user = $users[$row->user_id] ?? null;
            $userStats[] = [
                'user_id'       => $row->user_id,
                'user_name'     => $user->name ?? 'Unknown',
                'user_email'    => $user->email ?? '',
                'total_actions' => $row->total_actions,
                'creates'       => $row->creates,
                'updates'       => $row->updates,
                'deletes'       => $row->deletes,
                'first_activity' => $row->first_activity,
                'last_activity'  => $row->last_activity,
                'daily'         => ($dailyPerUser[$row->user_id] ?? collect())->map(fn($d) => [
                    'date'  => $d->date,
                    'count' => $d->count,
                ])->values(),
                'modules'       => ($modulePerUser[$row->user_id] ?? collect())->map(fn($m) => [
                    'module' => $m->auditable_type,
                    'count'  => $m->count,
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
            'date_range'       => [
                'start' => $dateStart,
                'end'   => $dateEnd,
            ],
        ]);
    }
}
