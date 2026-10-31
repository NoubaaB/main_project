<?php

namespace App\GraphQL;

use App\Http\Controllers\FortnightActivityController;
use App\Http\Controllers\WorkforceController;
use App\Http\Controllers\WorkforceMovementController;
use App\Http\Controllers\WorkforcePaymentController;
use App\Models\FortnightActivity;
use App\Models\Workforce;
use Illuminate\Http\Request;

class WorkforceGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    workforceStatistics(
        date_start: String!
        date_end: String!
        workforce_ids: [Int]
        section_ids: [Int]
        corporal_ids: [Int]
    ): WorkforceStatisticsResponse

    workforceMovementStatistics(
        start_date: String
        end_date: String
        granularity: String
        workforce_ids: [Int]
    ): WorkforceMovementStatisticsResponse

    workforceAbsences(
        threshold: Int
        reference_date: String
        status: String
        search: String
        team_id: Int
        corporal_id: Int
        section_id: Int
    ): WorkforceAbsenceResponse

    workforceMovementHistory(workforce_id: ID!): WorkforceMovementHistoryResponse

    workforceAbsenceSettings: WorkforceAbsenceSetting

    fortnightSummary(
        year: Int!
        month: Int!
        workforce_ids: [Int]
        workforce_id: Int
    ): FortnightSummaryResponse

    fortnightDailyTotals(
        year: Int!
        month: Int!
    ): [FortnightDailyTotal]

    fortnightActivities(
        year: Int
        month: Int
        workforce_ids: [Int]
        workforce_id: Int
        fortnight_period: String
        date: String
    ): [FortnightActivity]

    fortnightActivitiesByDate(
        date: String!
    ): [FortnightActivity]

    fortnightBreakdown(
        year: Int!
        month: Int!
        workforce_ids: [Int]
    ): FortnightBreakdownResponse

    workforces: [Workforce]
    workforce(id: ID!): Workforce
}

extend type Mutation {
    recordWorkforceMotif(
        workforce_id: ID!
        motif_date: String
        reason: String!
        notes: String
        start_date: String
        end_date: String
        days_count: Int
        is_justified: Boolean
    ): WorkforceMovementMutationResult

    workforceLeaveCompany(
        workforce_id: ID!
        departure_date: String!
        departure_reason: String
        notes: String
    ): WorkforceMovementMutationResult

    workforceReturnToWork(
        workforce_id: ID!
        return_date: String!
        restore_teams: Boolean
        notes: String
    ): WorkforceMovementMutationResult

    saveWorkforceAbsenceSetting(
        threshold_days: Int!
    ): WorkforceAbsenceSetting

    createFortnightActivity(
        workforce_id: Int!
        date: String!
        price: Float!
        section_id: Int
        corporal_id: Int
        is_fixed_price: Boolean
        fortnight_period: String
    ): FortnightActivityMutationResult

    updateFortnightActivity(
        id: ID!
        workforce_id: Int
        date: String
        price: Float
        section_id: Int
        corporal_id: Int
        is_fixed_price: Boolean
        fortnight_period: String
    ): FortnightActivityMutationResult

    deleteFortnightActivity(id: ID!): Boolean

    bulkDeleteFortnightActivities(
        date: String
        dates: [String]
        workforce_ids: [Int]
    ): BulkDeleteResponse

    toggleWorkforcePayment(
        workforce_id: Int!
        year: Int!
        month: Int!
        fortnight_period: String!
        is_paid: Boolean!
    ): WorkforcePaymentToggleResponse

    bulkToggleWorkforcePayments(
        workforce_ids: [Int]!
        year: Int!
        month: Int!
        fortnight_period: String!
        is_paid: Boolean!
    ): BulkPaymentToggleResponse
}

type Workforce {
    id: ID!
    full_name: String
    matricule: String
    nationality: String
    sex: String
    cni: String
    is_migrant: Boolean
    salary_statement: Boolean
    fixed_daily_pay: Boolean
    cnss_id: String
    daily_price: Float
    status: String
    departure_date: String
    departure_reason: String
    return_date: String
    teams: [Team]
    created_at: String
    updated_at: String
}

type Corporal {
    id: ID!
    full_name: String
    sex: String
    cni: String
    is_quinzaine: Boolean
    daily_price: Float
}

type Section {
    id: ID!
    name: String
    daily_price: Float
    is_price_modifiable: Boolean
}

type Team {
    id: ID!
    name: String
    corporal_id: Int
    description: String
}

type FortnightActivity {
    id: ID!
    workforce_id: Int
    date: String
    fortnight_period: String
    section_id: Int
    corporal_id: Int
    price: Float
    is_fixed_price: Boolean
    workforce: Workforce
    section: Section
    corporal: Corporal
    created_at: String
    updated_at: String
}

type FortnightDailyTotal {
    date: String
    total_price: Float
    count: Int
}

type FortnightPeriodSummary {
    total: Float
    fixed_total: Float
    non_fixed_total: Float
    working_days: Int
}

type FortnightSummaryResponse {
    first_fortnight: FortnightPeriodSummary
    second_fortnight: FortnightPeriodSummary
    month_total: Float
    month_fixed_total: Float
    month_non_fixed_total: Float
}

type FortnightBreakdownActivityItem {
    id: ID
    date: String
    section_name: String
    corporal_name: String
    price: Float
    is_fixed_price: Boolean
}

type FortnightBreakdownWorkforceDrill {
    id: ID
    name: String
    matricule: String
    cin: String
    cnss_id: String
    nationality: String
    sex: String
    is_migrant: Boolean
    salary_statement: Boolean
    fixed_daily_pay: Boolean
    q1_count: Int
    q1_total: Float
    q1_fixed_total: Float
    q1_non_fixed_total: Float
    q2_count: Int
    q2_total: Float
    q2_fixed_total: Float
    q2_non_fixed_total: Float
    month_total: Float
    month_fixed_total: Float
    month_non_fixed_total: Float
    month_count: Int
}

type FortnightBreakdownSection {
    id: ID!
    name: String
    q1_count: Int
    q1_total: Float
    q1_fixed_total: Float
    q1_non_fixed_total: Float
    q2_count: Int
    q2_total: Float
    q2_fixed_total: Float
    q2_non_fixed_total: Float
    month_total: Float
    month_fixed_total: Float
    month_non_fixed_total: Float
    month_count: Int
    workforces: [FortnightBreakdownWorkforceDrill]
}

type FortnightBreakdownCorporal {
    id: ID!
    name: String
    q1_count: Int
    q1_total: Float
    q1_fixed_total: Float
    q1_non_fixed_total: Float
    q2_count: Int
    q2_total: Float
    q2_fixed_total: Float
    q2_non_fixed_total: Float
    month_total: Float
    month_fixed_total: Float
    month_non_fixed_total: Float
    month_count: Int
    workforces: [FortnightBreakdownWorkforceDrill]
}

type FortnightBreakdownWorkforce {
    id: ID!
    name: String
    matricule: String
    nationality: String
    sex: String
    is_migrant: Boolean
    salary_statement: Boolean
    cnss_id: String
    cin: String
    fixed_daily_pay: Boolean
    daily_price: Float
    q1_count: Int
    q1_total: Float
    q1_fixed_total: Float
    q1_non_fixed_total: Float
    q2_count: Int
    q2_total: Float
    q2_fixed_total: Float
    q2_non_fixed_total: Float
    month_total: Float
    month_fixed_total: Float
    month_non_fixed_total: Float
    month_count: Int
    is_paid_q1: Boolean
    is_paid_q2: Boolean
    q1_work_days: Int
    q2_work_days: Int
    q1_non_work_days: Int
    q2_non_work_days: Int
    activities: [FortnightBreakdownActivityItem]
}

type FortnightBreakdownResponse {
    sections: [FortnightBreakdownSection]
    corporals: [FortnightBreakdownCorporal]
    workforces: [FortnightBreakdownWorkforce]
}

type WorkforceStatisticsResponse {
    daily: [WorkforceDailyStat]
    sections: [WorkforceSectionStat]
    corporals: [WorkforceCorporalStat]
    workforces: [WorkforceLeagueItem]
    grand_total: Float
    total_activities: Int
}

type WorkforceDailyStat {
    date: String
    total: Float
    count: Int
}

type WorkforceSectionStat {
    id: ID
    name: String
    total: Float
    count: Int
}

type WorkforceCorporalStat {
    id: ID
    name: String
    total: Float
    count: Int
}

type WorkforceLeagueItem {
    id: ID
    name: String
    matricule: String
    cin: String
    section: String
    corporal: String
    total: Float
    count: Int
    work_days: Int
}

type WorkforceMovementSummary {
    total_activities: Int
    total_amount: Float
    distinct_workforces_count: Int
    distinct_corporals_count: Int
    distinct_sections_count: Int
    distinct_teams_count: Int
    all_corporals: [String]
    all_sections: [String]
    all_teams: [String]
}

type WorkforceMovementWorkerSummary {
    id: ID
    full_name: String
    matricule: String
    cni: String
    corporals_count: Int
    corporals_list: [String]
    sections_count: Int
    sections_list: [String]
    teams_count: Int
    teams_list: [String]
    work_days_count: Int
    activities_count: Int
    total_amount: Float
}

type WorkforceMovementTimelineItem {
    workforce_id: ID
    workforce_name: String
    matricule: String
    period_key: String
    period_label: String
    corporal_id: Int
    corporal_name: String
    section_id: Int
    section_name: String
    team_names: [String]
    team_label: String
    activities_count: Int
    days_count: Int
    total_amount: Float
}

type WorkforceMovementStatisticsResponse {
    granularity: String
    start_date: String
    end_date: String
    summary: WorkforceMovementSummary
    workforce_summaries: [WorkforceMovementWorkerSummary]
    timeline: [WorkforceMovementTimelineItem]
}

type WorkforceAbsenceSummary {
    total_workforces: Int
    active_count: Int
    absent_count: Int
    left_company_count: Int
    total_absence_incidents: Int
    total_absence_days: Int
}

type WorkforceAbsenceTeam {
    id: ID
    name: String
    corporal_id: Int
}

type WorkforceAbsenceMovementItem {
    id: ID
    type: String
    start_date: String
    end_date: String
    days_count: Int
    reason: String
    notes: String
    restored_teams: Boolean
}

type WorkforceAbsenceItem {
    id: ID!
    full_name: String
    matricule: String
    cni: String
    sex: String
    nationality: String
    status: String
    db_status: String
    is_absent_by_threshold: Boolean
    last_activity_date: String
    days_since_last_activity: Int
    departure_date: String
    departure_reason: String
    return_date: String
    previous_team_ids: [Int]
    teams: [WorkforceAbsenceTeam]
    absence_count: Int
    total_absence_days: Int
    latest_motif: String
    latest_movement: WorkforceAbsenceMovementItem
}

type WorkforceAbsenceResponse {
    threshold_days: Int
    reference_date: String
    summary: WorkforceAbsenceSummary
    workforces: [WorkforceAbsenceItem]
}

type WorkforceMovement {
    id: ID!
    type: String
    start_date: String
    end_date: String
    days_count: Int
    reason: String
    notes: String
    restored_teams: Boolean
    previous_team_names: [String]
    created_by_name: String
    created_at: String
}

type WorkforceMovementHistoryResponse {
    workforce: WorkforceHistoryInfo
    movements: [WorkforceMovement]
}

type WorkforceHistoryInfo {
    id: ID!
    full_name: String
    matricule: String
    status: String
    teams: [WorkforceAbsenceTeam]
}

type WorkforceAbsenceSetting {
    threshold_days: Int
    message: String
}

type BulkDeleteResponse {
    deleted_count: Int
}

type WorkforcePaymentToggleResponse {
    payment: WorkforceFortnightPaymentItem
    message: String
}

type BulkPaymentToggleResponse {
    message: String
    count: Int
}

type WorkforceFortnightPaymentItem {
    id: ID
    workforce_id: Int
    year: Int
    month: Int
    fortnight_period: String
    is_paid: Boolean
}

type WorkforceMovementMutationResult {
    message: String
    movement: WorkforceAbsenceMovementItem
    workforce: Workforce
}

type FortnightActivityMutationResult {
    message: String
    activity: FortnightActivity
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            // ── Workforce Statistics (Dashboard & League) ──
            'workforceStatistics' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->statistics(new Request($args));
                return $resp->getData(true);
            },

            // ── Movement Tracker for Statistics ──
            'workforceMovementStatistics' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $resp = $controller->getStatisticsMovements(new Request($args));
                return $resp->getData(true);
            },

            // ── Workforce Absences & Movement Status ──
            'workforceAbsences' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $resp = $controller->getAbsences(new Request($args));
                return $resp->getData(true);
            },

            // ── Workforce Movement History Timeline ──
            'workforceMovementHistory' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $resp = $controller->getHistory($args['workforce_id']);
                return $resp->getData(true);
            },

            // ── Workforce Absence Settings ──
            'workforceAbsenceSettings' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $resp = $controller->getSetting();
                return $resp->getData(true);
            },

            // ── Fortnight Summary (Q1 / Q2 / Month) ──
            'fortnightSummary' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->summary(new Request($args));
                return $resp->getData(true);
            },

            // ── Fortnight Daily Totals (Overview Mode Calendar) ──
            'fortnightDailyTotals' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->dailyTotals(new Request($args));
                $data = $resp->getData(true);
                return $data['daily_totals'] ?? [];
            },

            // ── Fortnight Activities Listing ──
            'fortnightActivities' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);
                return $data['activities'] ?? [];
            },

            // ── Fortnight Activities for a specific Date (Day Activities Dialog) ──
            'fortnightActivitiesByDate' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->activitiesByDate(new Request($args));
                $data = $resp->getData(true);
                return $data['activities'] ?? [];
            },

            // ── Fortnight Multi-Dimensional Breakdown (Sections, Corporals, Workforces) ──
            'fortnightBreakdown' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->breakdown(new Request($args));
                return $resp->getData(true);
            },

            // ── All Workforces ──
            'workforces' => function ($root, array $args, $context) {
                return Workforce::with('teams')->orderBy('full_name')->get()->toArray();
            },

            // ── Single Workforce ──
            'workforce' => function ($root, array $args, $context) {
                return Workforce::with('teams')->find($args['id'] ?? null)?->toArray();
            },

            // ── Mutations ──
            'recordWorkforceMotif' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $wfId = $args['workforce_id'];
                unset($args['workforce_id']);
                $resp = $controller->recordMotif(new Request($args), $wfId);
                return $resp->getData(true);
            },

            'workforceLeaveCompany' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $wfId = $args['workforce_id'];
                unset($args['workforce_id']);
                $resp = $controller->leaveCompany(new Request($args), $wfId);
                return $resp->getData(true);
            },

            'workforceReturnToWork' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $wfId = $args['workforce_id'];
                unset($args['workforce_id']);
                $resp = $controller->returnToWork(new Request($args), $wfId);
                return $resp->getData(true);
            },

            'saveWorkforceAbsenceSetting' => function ($root, array $args, $context) {
                $controller = app(WorkforceMovementController::class);
                $resp = $controller->saveSetting(new Request($args));
                return $resp->getData(true);
            },

            'createFortnightActivity' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->store(new Request($args));
                return $resp->getData(true);
            },

            'updateFortnightActivity' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $id = $args['id'];
                unset($args['id']);
                $resp = $controller->update(new Request($args), $id);
                return $resp->getData(true);
            },

            'deleteFortnightActivity' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->destroy($args['id']);
                return $resp->getStatusCode() === 200;
            },

            'bulkDeleteFortnightActivities' => function ($root, array $args, $context) {
                $controller = app(FortnightActivityController::class);
                $resp = $controller->bulkDeleteByDate(new Request($args));
                return $resp->getData(true);
            },

            'toggleWorkforcePayment' => function ($root, array $args, $context) {
                $controller = app(WorkforcePaymentController::class);
                $resp = $controller->toggle(new Request($args));
                return $resp->getData(true);
            },

            'bulkToggleWorkforcePayments' => function ($root, array $args, $context) {
                $controller = app(WorkforcePaymentController::class);
                $resp = $controller->bulkToggle(new Request($args));
                return $resp->getData(true);
            },
        ];
    }
}
