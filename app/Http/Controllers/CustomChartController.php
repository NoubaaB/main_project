<?php

namespace App\Http\Controllers;

use App\Models\CustomChart;
use App\Models\CustomChartSeries;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomChartController extends Controller
{
    use BroadcastsModelChanges;

    public function index()
    {
        $charts = CustomChart::with('series')->where('is_active', true)->orderBy('display_order')->get();
        return response()->json(['custom_charts' => $charts]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'chart_type'      => 'required|string',
            'chart_options'   => 'nullable|array',
            'date_range_type' => 'required|in:dynamic,fixed_month,fixed_year,all',
            'fixed_month'     => 'nullable|integer|min:1|max:12',
            'fixed_year'      => 'nullable|integer|min:2000|max:2100',
            'dynamic_start'   => 'nullable|date',
            'dynamic_end'     => 'nullable|date',
            'display_type'    => 'required|in:chart,statistic',
            'display_order'   => 'nullable|integer',
            'grid_cols'       => 'nullable|integer|min:1|max:12',
            'is_active'       => 'nullable|boolean',
            'series'          => 'required|array|min:1',
            'series.*.name'              => 'required|string|max:255',
            'series.*.series_type'       => 'nullable|string',
            'series.*.color'             => 'nullable|string',
            'series.*.measure_unit'      => 'nullable|string|in:Kg,DH,DH/KG,%',
            'series.*.is_visible'        => 'nullable|boolean',
            'series.*.data_source'       => 'nullable|string',
            'series.*.aggregate_field'   => 'nullable|string',
            'series.*.aggregate_function'=> 'nullable|string',
            'series.*.group_by'          => 'nullable|string',
            'series.*.filters'           => 'nullable|array',
            'series.*.is_formula'        => 'nullable|boolean',
            'series.*.formula_expression'=> 'nullable|string',
            'series.*.formula_series_refs'=> 'nullable|array',
            'series.*.sort_order'        => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($data) {
            $seriesData = $data['series'];
            unset($data['series']);
            $data['created_by'] = Auth::id();

            $chart = CustomChart::create($data);

            foreach ($seriesData as $i => $s) {
                $s['custom_chart_id'] = $chart->id;
                $s['sort_order'] = $s['sort_order'] ?? $i;
                CustomChartSeries::create($s);
            }

            $chart->load('series');
            $this->broadcastChange('CustomChart', 'created', $chart->id, $chart->toArray());
            return response()->json(['custom_chart' => $chart], 201);
        });
    }

    public function show(CustomChart $customChart)
    {
        $customChart->load('series');
        return response()->json(['custom_chart' => $customChart]);
    }

    public function update(Request $request, CustomChart $customChart)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'chart_type'      => 'required|string',
            'chart_options'   => 'nullable|array',
            'date_range_type' => 'required|in:dynamic,fixed_month,fixed_year,all',
            'fixed_month'     => 'nullable|integer|min:1|max:12',
            'fixed_year'      => 'nullable|integer|min:2000|max:2100',
            'dynamic_start'   => 'nullable|date',
            'dynamic_end'     => 'nullable|date',
            'display_type'    => 'required|in:chart,statistic',
            'display_order'   => 'nullable|integer',
            'grid_cols'       => 'nullable|integer|min:1|max:12',
            'is_active'       => 'nullable|boolean',
            'series'          => 'required|array|min:1',
            'series.*.name'              => 'required|string|max:255',
            'series.*.series_type'       => 'nullable|string',
            'series.*.color'             => 'nullable|string',
            'series.*.measure_unit'      => 'nullable|string|in:Kg,DH,DH/KG,%',
            'series.*.is_visible'        => 'nullable|boolean',
            'series.*.data_source'       => 'nullable|string',
            'series.*.aggregate_field'   => 'nullable|string',
            'series.*.aggregate_function'=> 'nullable|string',
            'series.*.group_by'          => 'nullable|string',
            'series.*.filters'           => 'nullable|array',
            'series.*.is_formula'        => 'nullable|boolean',
            'series.*.formula_expression'=> 'nullable|string',
            'series.*.formula_series_refs'=> 'nullable|array',
            'series.*.sort_order'        => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($data, $customChart) {
            $seriesData = $data['series'];
            unset($data['series']);

            $customChart->update($data);
            $customChart->series()->delete();

            foreach ($seriesData as $i => $s) {
                $s['custom_chart_id'] = $customChart->id;
                $s['sort_order'] = $s['sort_order'] ?? $i;
                CustomChartSeries::create($s);
            }

            $customChart->load('series');
            $this->broadcastChange('CustomChart', 'updated', $customChart->id, $customChart->toArray());
            return response()->json(['custom_chart' => $customChart]);
        });
    }

    public function destroy(CustomChart $customChart)
    {
        $id = $customChart->id;
        $customChart->delete();
        $this->broadcastChange('CustomChart', 'deleted', $id);
        return response()->json(['message' => 'Deleted']);
    }

    public function reorder(Request $request)
    {
        $items = $request->validate(['items' => 'required|array', 'items.*.id' => 'required|integer', 'items.*.display_order' => 'required|integer']);
        foreach ($items['items'] as $item) {
            CustomChart::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }
        return response()->json(['message' => 'Reordered']);
    }

    /**
     * The core data engine — fetches and aggregates data for a chart.
     */
    public function chartData(Request $request, CustomChart $customChart)
    {
        $customChart->load('series');
        $dateRange = $this->resolveDateRange($customChart, $request);
        $groupBy = 'month';

        $seriesResults = [];
        $categories = [];

        // First pass: compute non-formula series
        foreach ($customChart->series as $s) {
            if ($s->is_formula) {
                $seriesResults[$s->id] = null; // placeholder
                continue;
            }
            $groupBy = $s->group_by ?: 'month';
            $result = $this->querySeriesData($s, $dateRange, $groupBy);
            $seriesResults[$s->id] = $result;

            // Merge categories
            foreach ($result['categories'] as $cat) {
                if (!in_array($cat, $categories)) $categories[] = $cat;
            }
        }

        sort($categories);

        // Align all non-formula series to unified categories
        foreach ($seriesResults as $id => &$r) {
            if ($r === null) continue;
            $aligned = [];
            foreach ($categories as $cat) {
                $idx = array_search($cat, $r['categories']);
                $aligned[] = $idx !== false ? $r['data'][$idx] : 0;
            }
            $r['data'] = $aligned;
            $r['categories'] = $categories;
        }
        unset($r);

        // Second pass: compute formula series
        foreach ($customChart->series as $s) {
            if (!$s->is_formula) continue;
            $seriesResults[$s->id] = $this->evaluateFormula($s, $seriesResults, $categories, $customChart);
        }

        // Build final output — only include visible series
        $output = [];
        foreach ($customChart->series->sortBy('sort_order') as $s) {
            if ($s->is_visible === false) continue;
            $r = $seriesResults[$s->id] ?? ['data' => [], 'categories' => $categories];
            $entry = [
                'name'         => $s->name,
                'type'         => $s->series_type ?: 'column',
                'color'        => $s->color,
                'measure_unit' => $s->measure_unit ?: 'Kg',
                'data'         => $r['data'],
            ];
            $output[] = $entry;
        }

        return response()->json([
            'categories' => $categories,
            'series'     => $output,
        ]);
    }

    private function resolveDateRange(CustomChart $chart, Request $request = null): array
    {
        switch ($chart->date_range_type) {
            case 'fixed_month':
                $y = $chart->fixed_year ?: now()->year;
                $m = $chart->fixed_month ?: now()->month;
                return [
                    'start' => "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01",
                    'end'   => date('Y-m-t', mktime(0, 0, 0, $m, 1, $y)),
                ];
            case 'fixed_year':
                $y = $chart->fixed_year ?: now()->year;
                return ['start' => "$y-01-01", 'end' => "$y-12-31"];
            case 'dynamic':
                // Allow dashboard to override via query params
                return [
                    'start' => $request?->query('start') ?: now()->startOfYear()->format('Y-m-d'),
                    'end'   => $request?->query('end') ?: now()->format('Y-m-d'),
                ];
            default: // 'all'
                return ['start' => null, 'end' => null];
        }
    }

    private function querySeriesData(CustomChartSeries $series, array $dateRange, string $groupBy): array
    {
        $source = $series->data_source;
        $field  = $series->aggregate_field ?: 'total_weight';
        $fn     = $series->aggregate_function ?: 'sum';
        $filters = $series->filters ?? [];

        // Static charges use a special aggregation engine
        if ($source === 'static_charges') {
            return $this->queryStaticCharges($field, $fn, $groupBy, $dateRange);
        }

        // Determine if we need detail-level query
        $useDetails = $this->needsDetailQuery($source, $filters, $field);

        if ($useDetails) {
            return $this->queryDetailLevel($source, $field, $fn, $groupBy, $dateRange, $filters);
        }

        return $this->queryParentLevel($source, $field, $fn, $groupBy, $dateRange, $filters);
    }

    private function needsDetailQuery(string $source, array $filters, string $field): bool
    {
        $detailFilters = ['stockable_type', 'stockable_id', 'fish_process_type_id', 'detail_category_id'];
        $detailFields  = ['weight', 'nbr_box'];

        foreach ($detailFilters as $f) {
            if (!empty($filters[$f])) return true;
        }
        if (in_array($field, $detailFields)) return true;

        return false;
    }

    private function queryParentLevel(string $source, string $field, string $fn, string $groupBy, array $dateRange, array $filters): array
    {
        $tableMap = [
            'charges'              => 'charges',
            'productions'          => 'productions',
            'shippings'            => 'shippings',
            'warehouses'           => 'warehouse_balances',
            'fortnight_activities' => 'fortnight_activities',
        ];

        $table = $tableMap[$source] ?? $source;
        $dateCol = $source === 'warehouses' ? 'date' : 'date';

        $groupExpr = $this->getGroupExpression($dateCol, $groupBy);

        $query = DB::table($table)->whereNull('deleted_at');

        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween($dateCol, [$dateRange['start'], $dateRange['end']]);
        }

        // Apply filters
        $categoryMap = [
            'charges'    => 'charge_category_id',
            'productions'=> 'production_category_id',
            'shippings'  => 'shipping_category_id',
        ];
        if (!empty($filters['category_id']) && isset($categoryMap[$source])) {
            $query->where($categoryMap[$source], $filters['category_id']);
        }

        foreach (['supplier_id', 'client_id', 'fishery_id', 'origin_id', 'ship_id', 'warehouse_id', 'section_id', 'corporal_id', 'workforce_id'] as $fk) {
            if (!empty($filters[$fk])) {
                $query->where($fk, $filters[$fk]);
            }
        }

        if (!empty($filters['production_type'])) {
            $query->where('production_type', $filters['production_type']);
        }

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['fortnight_period'])) {
            $query->where('fortnight_period', $filters['fortnight_period']);
        }

        // Warehouse-specific filters: join warehouses table for category & sejour
        if ($source === 'warehouses' && (!empty($filters['warehouse_category_id']) || isset($filters['has_sejour']))) {
            $query->join('warehouses', 'warehouse_balances.warehouse_id', '=', 'warehouses.id');

            if (!empty($filters['warehouse_category_id'])) {
                $query->where('warehouses.warehouse_category_id', $filters['warehouse_category_id']);
            }

            if (isset($filters['has_sejour']) && $filters['has_sejour'] !== null && $filters['has_sejour'] !== '') {
                $query->where('warehouses.has_sejour', filter_var($filters['has_sejour'], FILTER_VALIDATE_BOOLEAN));
            }
        }

        $aggExpr = $field === 'count'
            ? DB::raw("COUNT(*) as agg_value")
            : DB::raw(strtoupper($fn) . "($field) as agg_value");

        $rows = $query
            ->select(DB::raw("$groupExpr as period"), $aggExpr)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'categories' => $rows->pluck('period')->toArray(),
            'data'       => $rows->pluck('agg_value')->map(fn($v) => round((float)$v, 2))->toArray(),
        ];
    }

    private function queryDetailLevel(string $source, string $field, string $fn, string $groupBy, array $dateRange, array $filters): array
    {
        $detailMap = [
            'charges'     => ['detail_charges', 'charge_id', 'charges'],
            'productions' => ['detail_productions', 'production_id', 'productions'],
            'shippings'   => ['detail_shippings', 'shipping_id', 'shippings'],
        ];

        if (!isset($detailMap[$source])) {
            return $this->queryParentLevel($source, $field, $fn, $groupBy, $dateRange, $filters);
        }

        [$detailTable, $fkCol, $parentTable] = $detailMap[$source];
        $dateCol = "$parentTable.date";
        $groupExpr = $this->getGroupExpression($dateCol, $groupBy);

        $query = DB::table($detailTable)
            ->join($parentTable, "$detailTable.$fkCol", '=', "$parentTable.id")
            ->whereNull("$detailTable.deleted_at")
            ->whereNull("$parentTable.deleted_at");

        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween($dateCol, [$dateRange['start'], $dateRange['end']]);
        }

        // Parent-level filters
        $categoryMap = ['charges' => 'charge_category_id', 'productions' => 'production_category_id', 'shippings' => 'shipping_category_id'];
        if (!empty($filters['category_id']) && isset($categoryMap[$source])) {
            $query->where("$parentTable." . $categoryMap[$source], $filters['category_id']);
        }

        foreach (['supplier_id', 'client_id', 'fishery_id', 'origin_id', 'ship_id'] as $fk) {
            if (!empty($filters[$fk])) {
                $query->where("$parentTable.$fk", $filters[$fk]);
            }
        }

        // Detail-level filters
        if (!empty($filters['stockable_type'])) {
            $query->where("$detailTable.stockable_type", $filters['stockable_type']);
        }
        if (!empty($filters['stockable_id'])) {
            $query->where("$detailTable.stockable_id", $filters['stockable_id']);
        }
        if (!empty($filters['detail_category_id'])) {
            $catCol = str_replace('detail_', 'detail_', $detailTable);
            $catCol = "detail_{$source}_category_id";
            $catCol = rtrim($catCol, 's') . '_category_id';
            // charges -> detail_charge_category_id, productions -> detail_production_category_id
            $singularSource = rtrim($source, 's');
            $query->where("$detailTable.detail_{$singularSource}_category_id", $filters['detail_category_id']);
        }

        // Fish process type filter: join through stockable (fish_types) -> fish_process_type_id
        if (!empty($filters['fish_process_type_id'])) {
            $query->where("$detailTable.stockable_type", 'App\\Models\\FishType')
                  ->join('fish_types', function ($join) use ($detailTable) {
                      $join->on("$detailTable.stockable_id", '=', 'fish_types.id');
                  })
                  ->where('fish_types.fish_process_type_id', $filters['fish_process_type_id']);
        }

        // Determine aggregate field — map parent fields to detail fields
        $actualField = $field;
        if (in_array($field, ['total_weight', 'total_price'])) {
            $fieldMap = ['total_weight' => 'weight', 'total_price' => 'total_price'];
            $actualField = "$detailTable." . $fieldMap[$field];
        } elseif ($field === 'count') {
            $actualField = 'count';
        } else {
            $actualField = "$detailTable.$field";
        }

        $aggExpr = $field === 'count'
            ? DB::raw("COUNT(*) as agg_value")
            : DB::raw(strtoupper($fn) . "($actualField) as agg_value");

        $rows = $query
            ->select(DB::raw("$groupExpr as period"), $aggExpr)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'categories' => $rows->pluck('period')->toArray(),
            'data'       => $rows->pluck('agg_value')->map(fn($v) => round((float)$v, 2))->toArray(),
        ];
    }

    private function getGroupExpression(string $dateCol, string $groupBy): string
    {
        return match ($groupBy) {
            'day'   => "DATE($dateCol)",
            'year'  => "YEAR($dateCol)",
            default => "DATE_FORMAT($dateCol, '%Y-%m')",
        };
    }

    /**
     * Query static charges — flat charges summed per period.
     * For each period bucket, sums the amount of all static charges that were active during that period.
     */
    private function queryStaticCharges(string $field, string $fn, string $groupBy, array $dateRange): array
    {
        $charges = \App\Models\StaticCharge::where('is_active', true)->whereNull('deleted_at')->get();

        if ($charges->isEmpty()) {
            return ['categories' => [], 'data' => []];
        }

        // Build list of period buckets within the date range
        $start = $dateRange['start'] ? \Carbon\Carbon::parse($dateRange['start']) : \Carbon\Carbon::now()->subYears(5)->startOfYear();
        $end = $dateRange['end'] ? \Carbon\Carbon::parse($dateRange['end']) : \Carbon\Carbon::now();

        $periods = [];
        $current = $start->copy();

        if ($groupBy === 'day') {
            while ($current->lte($end)) {
                $periods[] = $current->format('Y-m-d');
                $current->addDay();
            }
        } elseif ($groupBy === 'year') {
            $current = $current->startOfYear();
            while ($current->lte($end)) {
                $periods[] = (string) $current->year;
                $current->addYear();
            }
        } else { // month
            $current = $current->startOfMonth();
            while ($current->lte($end)) {
                $periods[] = $current->format('Y-m');
                $current->addMonth();
            }
        }

        $categories = [];
        $data = [];

        foreach ($periods as $period) {
            // Determine the date to check activity against (mid-point of period)
            if ($groupBy === 'day') {
                $checkDate = $period;
            } elseif ($groupBy === 'year') {
                $checkDate = $period . '-07-01'; // mid-year
            } else {
                $checkDate = $period . '-15'; // mid-month
            }

            $activeCharges = $charges->filter(function ($c) use ($checkDate) {
                if ($c->date_range_type === 'all_time') return true;
                if ($c->date_range_type === 'date_range') {
                    return $c->date_start <= $checkDate && $c->date_end >= $checkDate;
                }
                if ($c->date_range_type === 'ongoing') {
                    return $c->date_start <= $checkDate;
                }
                return false;
            });

            if ($field === 'count') {
                $value = $activeCharges->count();
            } else {
                // field = 'amount'
                $values = $activeCharges->pluck('amount')->map(fn($v) => (float) $v);
                $value = match ($fn) {
                    'sum'   => $values->sum(),
                    'avg'   => $values->avg() ?: 0,
                    'min'   => $values->min() ?: 0,
                    'max'   => $values->max() ?: 0,
                    'count' => $values->count(),
                    default => $values->sum(),
                };
            }

            $categories[] = $period;
            $data[] = round((float) $value, 2);
        }

        return ['categories' => $categories, 'data' => $data];
    }

    private function evaluateFormula(CustomChartSeries $series, array $seriesResults, array $categories, CustomChart $chart): array
    {
        $expr = $series->formula_expression ?? '';
        $data = [];

        if (empty(trim($expr)) || empty($categories)) {
            return ['categories' => $categories, 'data' => array_fill(0, count($categories), 0)];
        }

        // Build lookup: S0, S1, ... mapped to series IDs using the already-loaded chart
        $allSeries = $chart->series->sortBy('sort_order')->values();
        $seriesById = [];
        foreach ($allSeries as $i => $s) {
            $seriesById["S$i"] = $seriesResults[$s->id] ?? ['data' => array_fill(0, count($categories), 0)];
        }

        for ($ci = 0; $ci < count($categories); $ci++) {
            $localExpr = $expr;
            // Replace longer aliases first (S10 before S1) to avoid partial matching
            $aliases = array_keys($seriesById);
            usort($aliases, fn($a, $b) => strlen($b) - strlen($a));
            foreach ($aliases as $alias) {
                $val = $seriesById[$alias]['data'][$ci] ?? 0;
                $localExpr = preg_replace('/\b' . preg_quote($alias, '/') . '\b/', (string)$val, $localExpr);
            }

            try {
                $result = $this->safeMathEval($localExpr);
                $data[] = is_finite($result) ? round($result, 2) : 0;
            } catch (\Throwable $e) {
                $data[] = 0;
            }
        }

        return ['categories' => $categories, 'data' => $data];
    }

    private function safeMathEval(string $expr): float
    {
        // Only allow numbers, operators, parentheses, decimal points, spaces
        $clean = preg_replace('/[^0-9+\-*\/().%\s]/', '', $expr);
        if (empty(trim($clean))) return 0;

        // Prevent division by zero
        $clean = preg_replace('/\/\s*0(?![.\d])/', '/ 0.0001', $clean);

        try {
            $result = eval("return (float)($clean);");
            return is_numeric($result) ? (float)$result : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
