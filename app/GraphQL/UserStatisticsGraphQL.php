<?php

namespace App\GraphQL;

use App\Http\Controllers\UserStatisticsController;
use Illuminate\Http\Request;

class UserStatisticsGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    userStatistics(
        date_start: String
        date_end: String
        user_id: Int
        module: String
    ): UserStatisticsResponse
}

type UserStatisticsResponse {
    user_stats: [UserStatItem]
    global_daily: [DailyCountItem]
    hourly: [HourlyCountItem]
    module_breakdown: [ModuleCountItem]
    recent_activity: [UserRecentActivity]
    today_stats: UserTodayStats
    period_summary: UserPeriodSummary
    date_range: DateRangeSummary
}

type UserStatItem {
    user_id: ID
    name: String
    email: String
    role: String
    total_actions: Int
    creates: Int
    updates: Int
    deletes: Int
    first_activity: String
    last_activity: String
    active_days: Int
    actions_per_active_day: Float
    sparkline: [Int]
    daily: [DailyCountItem]
    modules: [ModuleCountItem]
}

type DailyCountItem {
    date: String
    count: Int
}

type HourlyCountItem {
    hour: Int
    count: Int
}

type ModuleCountItem {
    auditable_type: String
    module: String
    count: Int
}

type UserRecentActivity {
    id: ID
    user_id: Int
    user_name: String
    user_email: String
    user_role: String
    auditable_type: String
    auditable_id: Int
    action: String
    created_at: String
}

type UserTodayStats {
    total: Int
    active_users: Int
    creates: Int
    updates: Int
    deletes: Int
}

type UserPeriodSummary {
    total_actions: Int
    active_users: Int
    creates: Int
    updates: Int
    deletes: Int
}

type DateRangeSummary {
    start: String
    end: String
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'userStatistics' => function ($root, array $args, $context) {
                $controller = app(UserStatisticsController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);

                if (!empty($data['module_breakdown']) && is_array($data['module_breakdown'])) {
                    foreach ($data['module_breakdown'] as &$mb) {
                        if (!isset($mb['module']) && isset($mb['auditable_type'])) {
                            $mb['module'] = $mb['auditable_type'];
                        }
                        if (!isset($mb['auditable_type']) && isset($mb['module'])) {
                            $mb['auditable_type'] = $mb['module'];
                        }
                    }
                }

                return $data;
            },
        ];
    }
}

