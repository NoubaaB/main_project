<?php

namespace App\Http\Controllers;

use App\Models\FortnightActivity;
use App\Models\WorkforceFortnightPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\BroadcastsModelChanges;

class FortnightActivityController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * List activities for a workforce in a given fortnight period.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workforce_id' => 'nullable|exists:workforces,id',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'fortnight_period' => 'nullable|in:Q1,Q2',
        ]);

        $query = FortnightActivity::with(['section', 'corporal', 'workforce'])
            ->whereYear('date', $request->year)
            ->whereMonth('date', $request->month)
            ->orderBy('date');

        // Support multiple workforce_ids OR single workforce_id
        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $query->whereIn('workforce_id', $request->workforce_ids);
        } elseif ($request->filled('workforce_id')) {
            $query->where('workforce_id', $request->workforce_id);
        } else {
            // No workforce filter provided — return empty to avoid loading everything
            return response()->json(['activities' => []], 200);
        }

        if ($request->fortnight_period) {
            $query->where('fortnight_period', $request->fortnight_period);
        }

        $activities = $query->get();

        return response()->json(['activities' => $activities], 200);
    }

    /**
     * Store a new fortnight activity.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workforce_id' => 'required|exists:workforces,id',
            'date' => 'required|date',
            'fortnight_period' => 'nullable|in:Q1,Q2',
            'section_id' => 'nullable|exists:sections,id',
            'corporal_id' => 'nullable|exists:corporals,id',
            'price' => 'required|numeric|min:0',
            'is_fixed_price' => 'boolean',
        ]);

        // Ensure at least one of section_id or corporal_id is provided
        if (empty($data['section_id']) && empty($data['corporal_id'])) {
            return response()->json([
                'message' => 'Either section or corporal must be selected.',
                'errors' => ['section_id' => ['Either section or corporal must be selected.']]
            ], 422);
        }

        // Ensure only one is provided
        if (!empty($data['section_id']) && !empty($data['corporal_id'])) {
            return response()->json([
                'message' => 'Only one of section or corporal can be selected.',
                'errors' => ['section_id' => ['Only one of section or corporal can be selected.']]
            ], 422);
        }
        // Auto-compute fortnight_period from date if not provided
        if (empty($data['fortnight_period'])) {
            $day = (int) date('d', strtotime($data['date']));
            $data['fortnight_period'] = $day <= 15 ? 'Q1' : 'Q2';
        }

        // Check if the target fortnight is marked as paid
        $isPaid = WorkforceFortnightPayment::where('workforce_id', $data['workforce_id'])
            ->where('year', date('Y', strtotime($data['date'])))
            ->where('month', date('n', strtotime($data['date'])))
            ->where('fortnight_period', $data['fortnight_period'])
            ->value('is_paid');

        if ($isPaid) {
            return response()->json([
                'message' => 'Cannot add activity: The payment for this fortnight period has already been processed.'
            ], 422);
        }

        $activity = FortnightActivity::create($data);
        $activity->load(['section', 'corporal', 'workforce']);
        $this->broadcastChange('FortnightActivity', 'created', $activity->id, $activity->toArray());

        return response()->json([
            'activity' => $activity
        ], 201);
    }

    /**
     * Update the specified activity.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $activity = FortnightActivity::findOrFail($id);

        $data = $request->validate([
            'date' => 'sometimes|date',
            'section_id' => 'nullable|exists:sections,id',
            'corporal_id' => 'nullable|exists:corporals,id',
            'price' => 'sometimes|numeric|min:0',
        ]);

        // If both are being updated, validate mutual exclusivity
        $sectionId = $data['section_id'] ?? $activity->section_id;
        $corporalId = $data['corporal_id'] ?? $activity->corporal_id;

        if (array_key_exists('section_id', $data) || array_key_exists('corporal_id', $data)) {
            $sectionId = $data['section_id'] ?? null;
            $corporalId = $data['corporal_id'] ?? null;

            if (empty($sectionId) && empty($corporalId)) {
                return response()->json([
                    'message' => 'Either section or corporal must be selected.',
                    'errors' => ['section_id' => ['Either section or corporal must be selected.']]
                ], 422);
            }

            if (!empty($sectionId) && !empty($corporalId)) {
                return response()->json([
                    'message' => 'Only one of section or corporal can be selected.',
                    'errors' => ['section_id' => ['Only one of section or corporal can be selected.']]
                ], 422);
            }
        }

        $dateToCheck = $data['date'] ?? $activity->date;
        $day = (int) date('d', strtotime($dateToCheck));
        $period = $day <= 15 ? 'Q1' : 'Q2';

        $isPaid = WorkforceFortnightPayment::where('workforce_id', $activity->workforce_id)
            ->where('year', date('Y', strtotime($dateToCheck)))
            ->where('month', date('n', strtotime($dateToCheck)))
            ->where('fortnight_period', $period)
            ->value('is_paid');

        if ($isPaid) {
            return response()->json([
                'message' => 'Cannot update activity: The payment for this fortnight period has already been processed.'
            ], 422);
        }

        $activity->update($data);
        $activity = $activity->fresh()->load(['section', 'corporal', 'workforce']);
        $this->broadcastChange('FortnightActivity', 'updated', $activity->id, $activity->toArray());

        return response()->json([
            'activity' => $activity
        ], 200);
    }

    /**
     * Remove the specified activity.
     */
    public function destroy($id): JsonResponse
    {
        $activity = FortnightActivity::findOrFail($id);

        $day = (int) date('d', strtotime($activity->date));
        $period = $day <= 15 ? 'Q1' : 'Q2';

        $isPaid = WorkforceFortnightPayment::where('workforce_id', $activity->workforce_id)
            ->where('year', date('Y', strtotime($activity->date)))
            ->where('month', date('n', strtotime($activity->date)))
            ->where('fortnight_period', $period)
            ->value('is_paid');

        if ($isPaid) {
            return response()->json([
                'message' => 'Cannot delete activity: The payment for this fortnight period has already been processed.'
            ], 422);
        }

        $activityId = $activity->id;
        $activity->delete();
        $this->broadcastChange('FortnightActivity', 'deleted', $activityId);

        return response()->json(['message' => 'Activity deleted successfully'], 200);
    }

    /**
     * Get summary totals based on actual date ranges (1-15 = Q1, 16-end = Q2).
     * Supports optional workforce filter: workforce_ids[] or workforce_id.
     * When no workforce filter is provided, aggregates across ALL workforces.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
            'workforce_id' => 'nullable|exists:workforces,id',
        ]);

        $year = $request->year;
        $month = $request->month;

        // Build base query — filter by year/month from the actual date column
        $baseQuery = FortnightActivity::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        // Filter by workforce(s)
        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $baseQuery->whereIn('workforce_id', $request->workforce_ids);
        } elseif ($request->filled('workforce_id')) {
            $baseQuery->where('workforce_id', $request->workforce_id);
        }
        // else: no workforce filter → all workforces

        // 1st Fortnight: days 1–15 (based on actual date, not stored period)
        $firstHalf = (clone $baseQuery)->whereDay('date', '<=', 15)->get();

        // 2nd Fortnight: days 16–end of month
        $secondHalf = (clone $baseQuery)->whereDay('date', '>', 15)->get();

        $firstHalfTotal = $firstHalf->sum('price');
        $firstHalfDays = $firstHalf->pluck('date')->unique()->count();

        $secondHalfTotal = $secondHalf->sum('price');
        $secondHalfDays = $secondHalf->pluck('date')->unique()->count();

        return response()->json([
            'first_fortnight' => [
                'total' => round($firstHalfTotal, 2),
                'working_days' => $firstHalfDays,
            ],
            'second_fortnight' => [
                'total' => round($secondHalfTotal, 2),
                'working_days' => $secondHalfDays,
            ],
            'month_total' => round($firstHalfTotal + $secondHalfTotal, 2),
        ], 200);
    }

    /**
     * Bulk delete activities for given workforce IDs on specific date(s).
     * Supports single `date` or multiple `dates` array.
     * When workforce_ids is not provided, deletes ALL activities for the date(s).
     */
    public function bulkDeleteByDate(Request $request): JsonResponse
    {
        $request->validate([
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
            'date' => 'nullable|date|required_without:dates',
            'dates' => 'nullable|array|required_without:date',
            'dates.*' => 'date',
        ]);

        // Collect target dates
        $dates = [];
        if ($request->has('dates') && is_array($request->dates) && count($request->dates) > 0) {
            $dates = $request->dates;
        } elseif ($request->has('date')) {
            $dates = [$request->date];
        }

        if (empty($dates)) {
            return response()->json(['message' => 'No dates provided', 'deleted_count' => 0], 422);
        }

        $query = FortnightActivity::query();

        if (count($dates) === 1) {
            $query->whereDate('date', $dates[0]);
        } else {
            // Use whereIn with date cast for multiple dates
            $query->whereIn(\DB::raw('DATE(date)'), $dates);
        }

        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $query->whereIn('workforce_id', $request->workforce_ids);
        }

        // Before deleting, check if any of these periods are paid
        // We will fetch the activities first to check their dates and workforces
        $activitiesToDelete = $query->get();
        foreach ($activitiesToDelete as $act) {
            $day = (int) date('d', strtotime($act->date));
            $period = $day <= 15 ? 'Q1' : 'Q2';
            $isPaid = WorkforceFortnightPayment::where('workforce_id', $act->workforce_id)
                ->where('year', date('Y', strtotime($act->date)))
                ->where('month', date('n', strtotime($act->date)))
                ->where('fortnight_period', $period)
                ->value('is_paid');
            if ($isPaid) {
                 return response()->json([
                    'message' => 'Cannot delete activities: One or more selected activities belong to a fortnight period that has already been paid.'
                 ], 422);
            }
        }

        $deleted = $query->delete();

        $this->broadcastChange('FortnightActivity', 'deleted', 0);

        return response()->json([
            'message' => 'Activities deleted successfully',
            'deleted_count' => $deleted,
        ], 200);
    }

    /**
     * Get aggregated daily totals for a month across all workforces.
     * Used to display the calendar overview when no specific workforce is selected.
     */
    public function dailyTotals(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $request->year;
        $month = $request->month;

        $activities = FortnightActivity::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('DATE(date) as date_key, SUM(price) as total_price, COUNT(*) as activity_count')
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->get();

        $dailyTotals = $activities->map(function ($row) {
            return [
                'date' => $row->date_key,
                'total_price' => round((float) $row->total_price, 2),
                'count' => (int) $row->activity_count,
            ];
        });

        return response()->json(['daily_totals' => $dailyTotals], 200);
    }

    /**
     * Get all activities for a specific date with full relationships.
     * Used when clicking a summary cell in overview mode.
     */
    public function activitiesByDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $activities = FortnightActivity::with(['section', 'corporal', 'workforce'])
            ->whereDate('date', $request->date)
            ->orderBy('workforce_id')
            ->get();

        return response()->json(['activities' => $activities], 200);
    }

    /**
     * Breakdown of activities grouped by section/corporal for a month.
     * Returns count and total price per assignment for Q1, Q2, and month.
     */
    public function breakdown(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
        ]);

        $year = $request->year;
        $month = $request->month;

        $query = FortnightActivity::with(['section', 'corporal', 'workforce'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $query->whereIn('workforce_id', $request->workforce_ids);
        }

        $activities = $query->get();
        
        // Fetch payments for the given month
        $paymentsQuery = WorkforceFortnightPayment::where('year', $year)->where('month', $month);
        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $paymentsQuery->whereIn('workforce_id', $request->workforce_ids);
        }
        $payments = $paymentsQuery->get()->groupBy('workforce_id');

        // Group by section
        $sections = [];
        $corporals = [];
        $workforces = [];

        foreach ($activities as $act) {
            $day = (int) date('d', strtotime($act->date));
            $half = $day <= 15 ? 'q1' : 'q2';
            $price = (float) $act->price;

            if ($act->section_id) {
                $key = $act->section_id;
                if (!isset($sections[$key])) {
                    $sections[$key] = [
                        'id' => $act->section_id,
                        'name' => $act->section ? $act->section->name : 'Unknown',
                        'q1_count' => 0,
                        'q1_total' => 0,
                        'q2_count' => 0,
                        'q2_total' => 0,
                    ];
                }
                $sections[$key][$half . '_count']++;
                $sections[$key][$half . '_total'] += $price;
            }

            if ($act->corporal_id) {
                $key = $act->corporal_id;
                if (!isset($corporals[$key])) {
                    $corporals[$key] = [
                        'id' => $act->corporal_id,
                        'name' => $act->corporal ? $act->corporal->full_name : 'Unknown',
                        'q1_count' => 0,
                        'q1_total' => 0,
                        'q2_count' => 0,
                        'q2_total' => 0,
                    ];
                }
                $corporals[$key][$half . '_count']++;
                $corporals[$key][$half . '_total'] += $price;
            }

            // Workforce grouping
            $wKey = $act->workforce_id;
            if ($wKey) {
                if (!isset($workforces[$wKey])) {
                    $workforces[$wKey] = [
                        'id' => $act->workforce_id,
                        'name' => $act->workforce ? $act->workforce->full_name : 'Unknown',
                        'matricule' => $act->workforce ? $act->workforce->matricule : '',
                        'nationality' => $act->workforce ? $act->workforce->nationality : '',
                        'sex' => $act->workforce ? $act->workforce->sex : '',
                        'is_migrant' => $act->workforce ? (bool) $act->workforce->is_migrant : false,
                        'salary_statement' => $act->workforce ? (bool) $act->workforce->salary_statement : false,
                        'cnss_id' => $act->workforce ? $act->workforce->cnss_id : '',
                        'cin' => $act->workforce ? $act->workforce->cin : '',
                        'fixed_daily_pay' => $act->workforce ? (bool) $act->workforce->fixed_daily_pay : false,
                        'daily_price' => $act->workforce ? $act->workforce->daily_price : null,
                        'q1_count' => 0,
                        'q1_total' => 0,
                        'q2_count' => 0,
                        'q2_total' => 0,
                        'q1_dates' => [],
                        'q2_dates' => [],
                        'activities' => [],
                    ];
                }
                $workforces[$wKey][$half . '_count']++;
                $workforces[$wKey][$half . '_total'] += $price;
                
                // Add payment status
                if (!isset($workforces[$wKey]['is_paid_q1'])) {
                    $wPayments = $payments->get($wKey) ?? collect([]);
                    $q1Payment = $wPayments->where('fortnight_period', 'Q1')->first();
                    $q2Payment = $wPayments->where('fortnight_period', 'Q2')->first();
                    $workforces[$wKey]['is_paid_q1'] = $q1Payment ? (bool) $q1Payment->is_paid : false;
                    $workforces[$wKey]['is_paid_q2'] = $q2Payment ? (bool) $q2Payment->is_paid : false;
                }

                // Track distinct work dates for work/non-work days
                $dateStr = date('Y-m-d', strtotime($act->date));
                $workforces[$wKey][$half . '_dates'][] = $dateStr;
                $workforces[$wKey]['activities'][] = [
                    'id' => $act->id,
                    'date' => $act->date,
                    'section_name' => $act->section ? $act->section->name : null,
                    'corporal_name' => $act->corporal ? $act->corporal->full_name : null,
                    'price' => $price,
                    'is_fixed_price' => (bool) $act->is_fixed_price,
                ];
            }
        }

        // Build drill-down for sections: group by workforce within each section
        foreach ($sections as &$s) {
            $sectionActivities = $activities->where('section_id', $s['id']);
            $sWorkforces = [];
            foreach ($sectionActivities as $act) {
                $wid = $act->workforce_id;
                $day = (int) date('d', strtotime($act->date));
                $half = $day <= 15 ? 'q1' : 'q2';
                $price = (float) $act->price;
                if (!isset($sWorkforces[$wid])) {
                    $sWorkforces[$wid] = [
                        'id' => $wid,
                        'name' => $act->workforce ? $act->workforce->full_name : 'Unknown',
                        'matricule' => $act->workforce ? $act->workforce->matricule : '',
                        'cin' => $act->workforce ? $act->workforce->cin : '',
                        'cnss_id' => $act->workforce ? $act->workforce->cnss_id : '',
                        'nationality' => $act->workforce ? $act->workforce->nationality : '',
                        'sex' => $act->workforce ? $act->workforce->sex : '',
                        'is_migrant' => $act->workforce ? (bool) $act->workforce->is_migrant : false,
                        'salary_statement' => $act->workforce ? (bool) $act->workforce->salary_statement : false,
                        'fixed_daily_pay' => $act->workforce ? (bool) $act->workforce->fixed_daily_pay : false,
                        'q1_count' => 0,
                        'q1_total' => 0,
                        'q2_count' => 0,
                        'q2_total' => 0,
                    ];
                }
                $sWorkforces[$wid][$half . '_count']++;
                $sWorkforces[$wid][$half . '_total'] += $price;
            }
            foreach ($sWorkforces as &$sw) {
                $sw['month_total'] = round($sw['q1_total'] + $sw['q2_total'], 2);
                $sw['month_count'] = $sw['q1_count'] + $sw['q2_count'];
                $sw['q1_total'] = round($sw['q1_total'], 2);
                $sw['q2_total'] = round($sw['q2_total'], 2);
            }
            $s['workforces'] = array_values($sWorkforces);
        }

        // Build drill-down for corporals: group by workforce within each corporal
        foreach ($corporals as &$c) {
            $corporalActivities = $activities->where('corporal_id', $c['id']);
            $cWorkforces = [];
            foreach ($corporalActivities as $act) {
                $wid = $act->workforce_id;
                $day = (int) date('d', strtotime($act->date));
                $half = $day <= 15 ? 'q1' : 'q2';
                $price = (float) $act->price;
                if (!isset($cWorkforces[$wid])) {
                    $cWorkforces[$wid] = [
                        'id' => $wid,
                        'name' => $act->workforce ? $act->workforce->full_name : 'Unknown',
                        'matricule' => $act->workforce ? $act->workforce->matricule : '',
                        'cin' => $act->workforce ? $act->workforce->cin : '',
                        'cnss_id' => $act->workforce ? $act->workforce->cnss_id : '',
                        'nationality' => $act->workforce ? $act->workforce->nationality : '',
                        'sex' => $act->workforce ? $act->workforce->sex : '',
                        'is_migrant' => $act->workforce ? (bool) $act->workforce->is_migrant : false,
                        'salary_statement' => $act->workforce ? (bool) $act->workforce->salary_statement : false,
                        'fixed_daily_pay' => $act->workforce ? (bool) $act->workforce->fixed_daily_pay : false,
                        'q1_count' => 0,
                        'q1_total' => 0,
                        'q2_count' => 0,
                        'q2_total' => 0,
                    ];
                }
                $cWorkforces[$wid][$half . '_count']++;
                $cWorkforces[$wid][$half . '_total'] += $price;
            }
            foreach ($cWorkforces as &$cw) {
                $cw['month_total'] = round($cw['q1_total'] + $cw['q2_total'], 2);
                $cw['month_count'] = $cw['q1_count'] + $cw['q2_count'];
                $cw['q1_total'] = round($cw['q1_total'], 2);
                $cw['q2_total'] = round($cw['q2_total'], 2);
            }
            $c['workforces'] = array_values($cWorkforces);
        }

        // Add month totals
        foreach ($sections as &$s) {
            $s['month_total'] = round($s['q1_total'] + $s['q2_total'], 2);
            $s['month_count'] = $s['q1_count'] + $s['q2_count'];
            $s['q1_total'] = round($s['q1_total'], 2);
            $s['q2_total'] = round($s['q2_total'], 2);
        }
        foreach ($corporals as &$c) {
            $c['month_total'] = round($c['q1_total'] + $c['q2_total'], 2);
            $c['month_count'] = $c['q1_count'] + $c['q2_count'];
            $c['q1_total'] = round($c['q1_total'], 2);
            $c['q2_total'] = round($c['q2_total'], 2);
        }
        foreach ($workforces as &$w) {
            $w['month_total'] = round($w['q1_total'] + $w['q2_total'], 2);
            $w['month_count'] = $w['q1_count'] + $w['q2_count'];
            $w['q1_total'] = round($w['q1_total'], 2);
            $w['q2_total'] = round($w['q2_total'], 2);
            // Work days = distinct dates with activities
            $w['q1_work_days'] = count(array_unique($w['q1_dates']));
            $w['q2_work_days'] = count(array_unique($w['q2_dates']));
            // Non-work days: Q1 = 15 days max, Q2 = remaining days in month
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $w['q1_non_work_days'] = max(0, 15 - $w['q1_work_days']);
            $w['q2_non_work_days'] = max(0, ($daysInMonth - 15) - $w['q2_work_days']);
            // Remove raw date arrays
            unset($w['q1_dates'], $w['q2_dates']);
        }

        return response()->json([
            'sections' => array_values($sections),
            'corporals' => array_values($corporals),
            'workforces' => array_values($workforces),
        ], 200);
    }

    /**
     * Export all activities for a month (for Excel export).
     * Returns row-level detail: workforce name, matricule, date, section/corporal, price.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
        ]);

        $year = $request->year;
        $month = $request->month;

        $query = FortnightActivity::with(['section', 'corporal', 'workforce'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date');

        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $query->whereIn('workforce_id', $request->workforce_ids);
        }

        $activities = $query->get()->map(function ($act) {
            return [
                'workforce_name' => $act->workforce ? $act->workforce->full_name : '',
                'matricule' => $act->workforce ? $act->workforce->matricule : '',
                'date' => $act->date,
                'type' => $act->section_id ? 'Section' : 'Corporal',
                'assignment' => $act->section_id
                    ? ($act->section ? $act->section->name : '')
                    : ($act->corporal ? $act->corporal->full_name : ''),
                'price' => (float) $act->price,
                'fortnight' => (int) date('d', strtotime($act->date)) <= 15 ? 'Q1' : 'Q2',
            ];
        });

        return response()->json(['rows' => $activities], 200);
    }

    /**
     * Statistics endpoint for dashboard charts.
     * Returns daily totals, section totals, and corporal totals.
     * Supports date range (months) and optional filters.
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'workforce_ids' => 'nullable|array',
            'workforce_ids.*' => 'exists:workforces,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
            'corporal_ids' => 'nullable|array',
            'corporal_ids.*' => 'exists:corporals,id',
        ]);

        $query = FortnightActivity::with(['section', 'corporal', 'workforce'])
            ->whereDate('date', '>=', $request->date_start)
            ->whereDate('date', '<=', $request->date_end);

        // Apply filters
        if ($request->has('workforce_ids') && is_array($request->workforce_ids) && count($request->workforce_ids) > 0) {
            $query->whereIn('workforce_id', $request->workforce_ids);
        }
        if ($request->has('section_ids') && is_array($request->section_ids) && count($request->section_ids) > 0) {
            $query->where(function ($q) use ($request) {
                $q->whereIn('section_id', $request->section_ids);
            });
        }
        if ($request->has('corporal_ids') && is_array($request->corporal_ids) && count($request->corporal_ids) > 0) {
            $query->where(function ($q) use ($request) {
                $q->whereIn('corporal_id', $request->corporal_ids);
            });
        }

        $activities = $query->orderBy('date')->get();

        // Daily totals
        $dailyMap = [];
        foreach ($activities as $act) {
            $dateStr = date('Y-m-d', strtotime($act->date));
            if (!isset($dailyMap[$dateStr])) {
                $dailyMap[$dateStr] = ['date' => $dateStr, 'total' => 0, 'count' => 0];
            }
            $dailyMap[$dateStr]['total'] += (float) $act->price;
            $dailyMap[$dateStr]['count']++;
        }
        $daily = array_values($dailyMap);

        // Section totals
        $sectionMap = [];
        foreach ($activities as $act) {
            if (!$act->section_id)
                continue;
            $key = $act->section_id;
            if (!isset($sectionMap[$key])) {
                $sectionMap[$key] = [
                    'id' => $act->section_id,
                    'name' => $act->section ? $act->section->name : 'Unknown',
                    'total' => 0,
                    'count' => 0,
                ];
            }
            $sectionMap[$key]['total'] += (float) $act->price;
            $sectionMap[$key]['count']++;
        }

        // Corporal totals
        $corporalMap = [];
        foreach ($activities as $act) {
            if (!$act->corporal_id)
                continue;
            $key = $act->corporal_id;
            if (!isset($corporalMap[$key])) {
                $corporalMap[$key] = [
                    'id' => $act->corporal_id,
                    'name' => $act->corporal ? $act->corporal->full_name : 'Unknown',
                    'total' => 0,
                    'count' => 0,
                ];
            }
            $corporalMap[$key]['total'] += (float) $act->price;
            $corporalMap[$key]['count']++;
        }

        $grandTotal = $activities->sum('price');

        return response()->json([
            'daily' => $daily,
            'sections' => array_values($sectionMap),
            'corporals' => array_values($corporalMap),
            'grand_total' => round((float) $grandTotal, 2),
            'total_activities' => $activities->count(),
        ], 200);
    }
}
