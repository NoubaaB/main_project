<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Charge;
use App\Models\Production;
use App\Models\Shipping;
use App\Models\ChargeCategory;
use App\Models\DetailChargeCategory;
use App\Models\ProductionCategory;
use App\Models\DetailProductionCategory;
use App\Models\ShippingCategory;
use App\Models\DetailShippingCategory;
use App\Models\CostConfiguration;
use Carbon\Carbon;

class JupiterDashboardController extends Controller
{
    /**
     * GET /api/jupiter-dashboard/chart-data
     *
     * Returns time-series chart data and summary totals for charges, productions,
     * and shippings, filtered by date range, periodicity, measures, and dynamic filters.
     */
    public function chartData(Request $request)
    {
        $request->validate([
            'start'       => 'required|date',
            'end'         => 'required|date|after_or_equal:start',
            'periodicity' => 'nullable|in:daily,monthly,yearly',
            'measures'    => 'nullable|array',
            'measures.*'  => 'in:weight,price',
            'filters'     => 'nullable|json',
        ]);

        $start       = Carbon::parse($request->start)->startOfDay();
        $end         = Carbon::parse($request->end)->endOfDay();
        $periodicity = $request->input('periodicity', 'daily');
        $measures    = $request->input('measures', ['weight', 'price']);
        $filters     = $request->input('filters') ? json_decode($request->input('filters'), true) : [];

        // Build period grouping SQL
        $periodSelect = $this->getPeriodSelect($periodicity);
        $periodGroup  = $this->getPeriodGroup($periodicity);

        // Generate all period labels for the date range
        $categories = $this->generateCategories($start, $end, $periodicity);

        $series  = [];
        $summary = [];

        // --- CHARGES ---
        $chargesQuery = Charge::whereBetween('date', [$start, $end]);
        $this->applyFilters($chargesQuery, $filters, 'charges', 'detailCharges');

        $chargesSummary = (clone $chargesQuery)->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')->first();
        $summary['charges'] = [
            'count'        => (int) $chargesSummary->cnt,
            'total_weight' => round((float) $chargesSummary->tw, 2),
            'total_price'  => round((float) $chargesSummary->tp, 2),
        ];

        if (in_array('weight', $measures)) {
            $series[] = $this->buildSeries($chargesQuery, 'charges', 'weight', $periodSelect, $periodGroup, $categories);
        }
        if (in_array('price', $measures)) {
            $series[] = $this->buildSeries($chargesQuery, 'charges', 'price', $periodSelect, $periodGroup, $categories);
        }

        // --- PRODUCTIONS ---
        $productionsQuery = Production::whereBetween('date', [$start, $end]);
        $this->applyFilters($productionsQuery, $filters, 'productions', 'detailProductions');

        $prodSummary = (clone $productionsQuery)->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')->first();
        $summary['productions'] = [
            'count'        => (int) $prodSummary->cnt,
            'total_weight' => round((float) $prodSummary->tw, 2),
            'total_price'  => round((float) $prodSummary->tp, 2),
        ];

        if (in_array('weight', $measures)) {
            $series[] = $this->buildSeries($productionsQuery, 'productions', 'weight', $periodSelect, $periodGroup, $categories);
        }
        if (in_array('price', $measures)) {
            $series[] = $this->buildSeries($productionsQuery, 'productions', 'price', $periodSelect, $periodGroup, $categories);
        }

        // --- SHIPPINGS ---
        $shippingsQuery = Shipping::whereBetween('date', [$start, $end]);
        $this->applyFilters($shippingsQuery, $filters, 'shippings', 'detailShippings');

        $shipSummary = (clone $shippingsQuery)->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')->first();
        $summary['shippings'] = [
            'count'        => (int) $shipSummary->cnt,
            'total_weight' => round((float) $shipSummary->tw, 2),
            'total_price'  => round((float) $shipSummary->tp, 2),
        ];

        if (in_array('weight', $measures)) {
            $series[] = $this->buildSeries($shippingsQuery, 'shippings', 'weight', $periodSelect, $periodGroup, $categories);
        }
        if (in_array('price', $measures)) {
            $series[] = $this->buildSeries($shippingsQuery, 'shippings', 'price', $periodSelect, $periodGroup, $categories);
        }

        // --- COST LINES (from CostConfiguration) ---
        $costLines = $this->buildCostLines($start, $end, $periodSelect, $periodGroup, $categories, $filters);

        return response()->json([
            'categories' => $categories,
            'series'     => $series,
            'summary'    => $summary,
            'cost_lines' => $costLines,
        ]);
    }

    /**
     * GET /api/jupiter-dashboard/filter-options
     *
     * Returns available categories and column definitions for the filter builder UI.
     */
    public function filterOptions()
    {
        return response()->json([
            'charge_categories'            => ChargeCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get(),
            'detail_charge_categories'     => DetailChargeCategory::select('id', 'name_fr', 'name_en', 'name_es', 'charge_category_id')->get(),
            'production_categories'        => ProductionCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get(),
            'detail_production_categories' => DetailProductionCategory::select('id', 'name_fr', 'name_en', 'name_es', 'production_category_id')->get(),
            'shipping_categories'          => ShippingCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')->get(),
            'detail_shipping_categories'   => DetailShippingCategory::select('id', 'name_fr', 'name_en', 'name_es', 'shipping_category_id')->get(),

            // Column definitions per table for the filter builder
            'columns' => [
                'charges' => [
                    ['key' => 'total_price',         'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'total_weight',        'type' => 'number', 'label' => 'total_weight'],
                    ['key' => 'charge_category_id',  'type' => 'select', 'label' => 'charge_category_id', 'ref' => 'charge_categories'],
                    ['key' => 'supplier_id',         'type' => 'number', 'label' => 'supplier_id'],
                    ['key' => 'fishery_id',          'type' => 'number', 'label' => 'fishery_id'],
                    ['key' => 'ship_id',             'type' => 'number', 'label' => 'ship_id'],
                    ['key' => 'origin_id',           'type' => 'number', 'label' => 'origin_id'],
                ],
                'detail_charges' => [
                    ['key' => 'detail_charge_category_id', 'type' => 'select', 'label' => 'detail_charge_category_id', 'ref' => 'detail_charge_categories'],
                    ['key' => 'weight',       'type' => 'number', 'label' => 'weight'],
                    ['key' => 'unit_price',   'type' => 'number', 'label' => 'unit_price'],
                    ['key' => 'total_price',  'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'stockable_type', 'type' => 'text', 'label' => 'stockable_type'],
                    ['key' => 'stockable_id',   'type' => 'number', 'label' => 'stockable_id'],
                    ['key' => 'warehouse_id',   'type' => 'number', 'label' => 'warehouse_id'],
                    ['key' => 'nbr_box',        'type' => 'number', 'label' => 'nbr_box'],
                ],
                'productions' => [
                    ['key' => 'total_price',              'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'total_weight',             'type' => 'number', 'label' => 'total_weight'],
                    ['key' => 'production_category_id',   'type' => 'select', 'label' => 'production_category_id', 'ref' => 'production_categories'],
                    ['key' => 'production_type',          'type' => 'text',   'label' => 'production_type'],
                    ['key' => 'supplier_id',              'type' => 'number', 'label' => 'supplier_id'],
                    ['key' => 'client_id',                'type' => 'number', 'label' => 'client_id'],
                ],
                'detail_productions' => [
                    ['key' => 'detail_production_category_id', 'type' => 'select', 'label' => 'detail_production_category_id', 'ref' => 'detail_production_categories'],
                    ['key' => 'weight',       'type' => 'number', 'label' => 'weight'],
                    ['key' => 'unit_price',   'type' => 'number', 'label' => 'unit_price'],
                    ['key' => 'total_price',  'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'stockable_type', 'type' => 'text', 'label' => 'stockable_type'],
                    ['key' => 'stockable_id',   'type' => 'number', 'label' => 'stockable_id'],
                    ['key' => 'warehouse_id',   'type' => 'number', 'label' => 'warehouse_id'],
                    ['key' => 'nbr_box',        'type' => 'number', 'label' => 'nbr_box'],
                ],
                'shippings' => [
                    ['key' => 'total_price',             'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'total_weight',            'type' => 'number', 'label' => 'total_weight'],
                    ['key' => 'shipping_category_id',    'type' => 'select', 'label' => 'shipping_category_id', 'ref' => 'shipping_categories'],
                    ['key' => 'direction',               'type' => 'text',   'label' => 'direction'],
                    ['key' => 'client_id',               'type' => 'number', 'label' => 'client_id'],
                ],
                'detail_shippings' => [
                    ['key' => 'detail_shipping_category_id', 'type' => 'select', 'label' => 'detail_shipping_category_id', 'ref' => 'detail_shipping_categories'],
                    ['key' => 'weight',       'type' => 'number', 'label' => 'weight'],
                    ['key' => 'unit_price',   'type' => 'number', 'label' => 'unit_price'],
                    ['key' => 'total_price',  'type' => 'number', 'label' => 'total_price'],
                    ['key' => 'stockable_type', 'type' => 'text', 'label' => 'stockable_type'],
                    ['key' => 'stockable_id',   'type' => 'number', 'label' => 'stockable_id'],
                    ['key' => 'warehouse_id',   'type' => 'number', 'label' => 'warehouse_id'],
                    ['key' => 'nbr_box',        'type' => 'number', 'label' => 'nbr_box'],
                ],
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    //  Private helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * Apply dynamic filter groups to a query builder.
     */
    private function applyFilters($query, array $filters, string $tableName, string $detailRelation)
    {
        foreach ($filters as $group) {
            if (($group['table'] ?? '') !== $tableName) {
                continue;
            }

            // Apply parent-level rules
            if (!empty($group['rules'])) {
                foreach ($group['rules'] as $rule) {
                    $this->applyRule($query, $rule);
                }
            }

            // Apply detail-level rules via whereHas
            if (!empty($group['detail_rules'])) {
                $detailRules = $group['detail_rules'];
                $query->whereHas($detailRelation, function ($q) use ($detailRules) {
                    foreach ($detailRules as $rule) {
                        $this->applyRule($q, $rule);
                    }
                });
            }
        }
    }

    /**
     * Apply a single filter rule to a query builder.
     */
    private function applyRule($query, array $rule)
    {
        $column   = $rule['column']   ?? null;
        $operator = $rule['operator'] ?? '=';
        $value    = $rule['value']    ?? null;

        if (!$column) return;

        // Whitelist of allowed columns to prevent SQL injection
        $allowed = [
            'total_price', 'total_weight', 'charge_category_id', 'supplier_id',
            'fishery_id', 'ship_id', 'origin_id', 'detail_charge_category_id',
            'weight', 'unit_price', 'stockable_type', 'stockable_id',
            'warehouse_id', 'nbr_box', 'production_category_id', 'production_type',
            'client_id', 'shipping_category_id', 'direction',
            'detail_production_category_id', 'detail_shipping_category_id',
        ];
        if (!in_array($column, $allowed)) return;

        // Whitelist operators
        $allowedOps = ['=', '!=', '>', '<', '>=', '<=', 'between', 'like'];
        if (!in_array($operator, $allowedOps)) return;

        if ($operator === 'between') {
            if (is_array($value) && count($value) === 2) {
                $query->whereBetween($column, [$value[0], $value[1]]);
            }
        } elseif ($operator === 'like') {
            $query->where($column, 'LIKE', '%' . $value . '%');
        } else {
            $query->where($column, $operator, $value);
        }
    }

    /**
     * Build a single series array for a given data type and measure.
     */
    private function buildSeries($baseQuery, string $type, string $measure, string $periodSelect, string $periodGroup, array $categories): array
    {
        $sumColumn = $measure === 'weight' ? 'total_weight' : 'total_price';
        $label     = ucfirst($type) . ' ' . ucfirst($measure);

        $rows = (clone $baseQuery)
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM({$sumColumn}), 0) as total")
            ->groupByRaw($periodGroup)
            ->pluck('total', 'period')
            ->toArray();

        // Map rows to categories (fill missing periods with 0)
        $data = [];
        foreach ($categories as $cat) {
            $data[] = round((float) ($rows[$cat] ?? 0), 2);
        }

        return [
            'name'    => $label,
            'type'    => $type,
            'measure' => $measure,
            'data'    => $data,
        ];
    }

    /**
     * Get the SQL SELECT expression for period grouping.
     */
    private function getPeriodSelect(string $periodicity): string
    {
        return match ($periodicity) {
            'monthly' => "DATE_FORMAT(date, '%Y-%m')",
            'yearly'  => "YEAR(date)",
            default   => "DATE(date)",
        };
    }

    /**
     * Get the SQL GROUP BY expression for period grouping.
     */
    private function getPeriodGroup(string $periodicity): string
    {
        return match ($periodicity) {
            'monthly' => "DATE_FORMAT(date, '%Y-%m')",
            'yearly'  => "YEAR(date)",
            default   => "DATE(date)",
        };
    }

    /**
     * Generate all period labels between start and end dates.
     */
    private function generateCategories(Carbon $start, Carbon $end, string $periodicity): array
    {
        $categories = [];
        $cursor     = $start->copy();

        switch ($periodicity) {
            case 'monthly':
                $cursor = $cursor->startOfMonth();
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m');
                    $cursor->addMonth();
                }
                break;

            case 'yearly':
                while ($cursor->year <= $end->year) {
                    $categories[] = (string) $cursor->year;
                    $cursor->addYear();
                }
                break;

            default: // daily
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }
                break;
        }

        return $categories;
    }

    /**
     * Build cost configuration lines for the chart.
     * Each enabled cost config with show_in_chart=true gets its formula evaluated per period.
     */
    private function buildCostLines(Carbon $start, Carbon $end, string $periodSelect, string $periodGroup, array $categories, array $filters): array
    {
        $costConfigs = CostConfiguration::where('enabled', true)
            ->where('show_in_chart', true)
            ->get();

        if ($costConfigs->isEmpty()) {
            return [];
        }

        $costLines = [];

        foreach ($costConfigs as $config) {
            $variables = $config->variables ?? [];
            if (empty($variables) || !$config->formula) {
                continue;
            }

            // Fetch per-period data for each variable
            $varPerPeriodData = [];
            foreach ($variables as $var) {
                $varPerPeriodData[$var['var_name']] = $this->getVariablePerPeriodData(
                    $var, $start, $end, $periodSelect, $periodGroup, $categories, $filters
                );
            }

            // Evaluate formula for each period
            $data = [];
            foreach ($categories as $idx => $cat) {
                $values = [];
                foreach ($variables as $var) {
                    $values[$var['var_name']] = $varPerPeriodData[$var['var_name']][$idx] ?? 0;
                }

                $data[] = $this->evaluateCostFormula($config->formula, $values);
            }

            $costLines[] = [
                'id'    => $config->id,
                'name'  => $config->name_fr,
                'name_en' => $config->name_en,
                'name_es' => $config->name_es,
                'unit'  => $config->unit,
                'color' => $config->chart_color ?? '#FF5722',
                'data'  => $data,
            ];
        }

        return $costLines;
    }

    /**
     * Get per-period aggregated data for a single cost variable.
     */
    private function getVariablePerPeriodData(array $var, Carbon $start, Carbon $end, string $periodSelect, string $periodGroup, array $categories, array $filters): array
    {
        $dataSource = $var['data_source'] ?? 'charge';
        $valueType  = $var['value_type'] ?? 'price';
        $categoryId = $var['category_id'] ?? null;
        $sumColumn  = $valueType === 'weight' ? 'total_weight' : 'total_price';

        // Map data source to model and date column
        switch ($dataSource) {
            case 'charge':
                $query = Charge::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'charges', 'detailCharges');
                if ($categoryId) {
                    $query->where('charge_category_id', $categoryId);
                }
                break;

            case 'production':
                $query = Production::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'productions', 'detailProductions');
                if ($categoryId) {
                    $query->where('production_category_id', $categoryId);
                }
                break;

            case 'shipping':
                $query = Shipping::whereBetween('date', [$start, $end]);
                $this->applyFilters($query, $filters, 'shippings', 'detailShippings');
                if ($categoryId) {
                    $query->where('shipping_category_id', $categoryId);
                }
                break;

            case 'storage_invoice':
                $query = \App\Models\StorageInvoice::whereBetween('date', [$start, $end]);
                $sumColumn = $valueType === 'weight' ? 'total_weight' : 'total_price';
                break;

            case 'fortnight_activities':
                $query = \App\Models\FortnightActivity::whereBetween('date', [$start, $end]);
                $sumColumn = 'total_price'; // Always price for fortnight
                break;

            default:
                return array_fill(0, count($categories), 0);
        }

        $rows = $query
            ->selectRaw("{$periodSelect} as period, COALESCE(SUM({$sumColumn}), 0) as total")
            ->groupByRaw($periodGroup)
            ->pluck('total', 'period')
            ->toArray();

        $result = [];
        foreach ($categories as $cat) {
            $result[] = round((float) ($rows[$cat] ?? 0), 2);
        }

        return $result;
    }

    /**
     * Evaluate a cost formula with given variable values (lightweight version).
     */
    private function evaluateCostFormula(string $formula, array $values): float
    {
        try {
            $expr = trim($formula);

            // Sort by variable name length descending (so c10 is replaced before c1)
            uksort($values, fn($a, $b) => strlen($b) <=> strlen($a));

            foreach ($values as $varName => $val) {
                $numVal = is_numeric($val) ? (float) $val : 0;
                $pattern = '/\b' . preg_quote($varName, '/') . '\b/';
                $expr = preg_replace($pattern, (string) $numVal, $expr);
            }

            // Convert percentages
            $expr = preg_replace('/(\d+(\.\d+)?)%/', '($1/100)', $expr);

            // Sanitize
            if (preg_match('/[^0-9\+\-\*\/\%\(\)\.\s]/', $expr)) {
                return 0;
            }

            // Evaluate using the existing math parser
            $result = $this->evalMathExpression($expr);

            return ($result !== null && !is_nan($result) && !is_infinite($result))
                ? round($result, 4)
                : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Lightweight safe math parser in pure PHP without eval().
     */
    private function evalMathExpression(string $expr): ?float
    {
        $expr = str_replace(' ', '', $expr);
        if ($expr === '') return 0.0;

        $expr = preg_replace('/(\d+(\.\d+)?)%/', '($1/100)', $expr);

        while (preg_match('/\(([^()]+)\)/', $expr, $matches)) {
            $subVal = $this->evalSimpleMath($matches[1]);
            if ($subVal === null) return null;
            $expr = str_replace($matches[0], (string) $subVal, $expr);
        }

        return $this->evalSimpleMath($expr);
    }

    private function evalSimpleMath(string $expr): ?float
    {
        preg_match_all('/(?:\d+\.?\d*|\.\d+)|[\+\-\*\/\%]/', $expr, $tokens);
        $tokens = $tokens[0] ?? [];
        if (empty($tokens)) return null;

        $numTokens = [];
        $i = 0;
        $cnt = count($tokens);

        while ($i < $cnt) {
            $t = $tokens[$i];
            if (($t === '-' || $t === '+') && ($i === 0 || in_array($tokens[$i - 1], ['+', '-', '*', '/', '%']))) {
                $next = $tokens[$i + 1] ?? '0';
                $numTokens[] = (float) ($t . $next);
                $i += 2;
            } else if (is_numeric($t)) {
                $numTokens[] = (float) $t;
                $i++;
            } else {
                $numTokens[] = $t;
                $i++;
            }
        }

        $pass1 = [];
        $j = 0;
        $numCnt = count($numTokens);

        while ($j < $numCnt) {
            $token = $numTokens[$j];
            if ($token === '*' || $token === '/' || $token === '%') {
                $prev = array_pop($pass1);
                $next = $numTokens[$j + 1] ?? 0;
                if ($token === '*') {
                    $pass1[] = $prev * $next;
                } else if ($token === '/') {
                    if ((float) $next === 0.0) return null;
                    $pass1[] = $prev / $next;
                } else if ($token === '%') {
                    if ((float) $next === 0.0) return null;
                    $pass1[] = fmod($prev, $next);
                }
                $j += 2;
            } else {
                $pass1[] = $token;
                $j++;
            }
        }

        if (empty($pass1)) return 0.0;
        $res = is_numeric($pass1[0]) ? (float) $pass1[0] : 0.0;
        $k = 1;
        $p1Cnt = count($pass1);

        while ($k < $p1Cnt) {
            $op = $pass1[$k];
            $val = $pass1[$k + 1] ?? 0;
            if ($op === '+') {
                $res += (float) $val;
            } else if ($op === '-') {
                $res -= (float) $val;
            }
            $k += 2;
        }

        return $res;
    }
}
