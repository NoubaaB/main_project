<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$dateFrom = '2026-09-01';
$dateTo = '2026-10-29';
$granularity = 'daily';
$warehouseIds = [];

// Base queries
$inflowsQuery = DB::table('warehouse_balances as wb')
    ->join('warehouses as w', 'w.id', '=', 'wb.warehouse_id')
    ->where('wb.warehouse_operation', 'stock_in')
    ->whereNull('wb.deleted_at')
    ->whereBetween('wb.date', [$dateFrom, $dateTo])
    ->selectRaw('
        wb.id as movement_id,
        wb.date as movement_date,
        "IN" as direction,
        wb.reception_tracking_number,
        wb.stockable_type,
        wb.stockable_id,
        wb.warehouse_id,
        w.name as warehouse_name,
        wb.total_weight as weight,
        wb.package_count as boxes,
        wb.unit_price,
        COALESCE(wb.total_price, wb.total_weight * wb.unit_price) as valuation,
        wb.supplier_name as partner_name
    ');

$outflowsQuery = DB::table('warehouse_balance_operations as wbo')
    ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
    ->join('warehouses as w', 'w.id', '=', 'wb.warehouse_id')
    ->whereNull('wbo.deleted_at')
    ->whereNull('wb.deleted_at')
    ->whereBetween('wbo.date', [$dateFrom, $dateTo])
    ->selectRaw('
        wbo.id as movement_id,
        wbo.date as movement_date,
        "OUT" as direction,
        wb.reception_tracking_number,
        wb.stockable_type,
        wb.stockable_id,
        wb.warehouse_id,
        w.name as warehouse_name,
        wbo.weight,
        wbo.package_count as boxes,
        wbo.unit_price,
        COALESCE(wbo.total_price, wbo.weight * wbo.unit_price) as valuation,
        wbo.client_name as partner_name
    ');

if (!empty($warehouseIds)) {
    $inflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
    $outflowsQuery->whereIn('wb.warehouse_id', $warehouseIds);
}

$unionQuery = $inflowsQuery->unionAll($outflowsQuery);

// Summary
$summaryRow = DB::table(DB::raw("({$unionQuery->toSql()}) as u"))
    ->mergeBindings($unionQuery)
    ->selectRaw("
        COUNT(*) as movements_count,
        SUM(CASE WHEN direction = 'IN' THEN 1 ELSE 0 END) as in_count,
        SUM(CASE WHEN direction = 'OUT' THEN 1 ELSE 0 END) as out_count,
        COALESCE(SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END), 0) as in_weight,
        COALESCE(SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END), 0) as out_weight,
        COALESCE(SUM(CASE WHEN direction = 'IN' THEN boxes ELSE 0 END), 0) as in_boxes,
        COALESCE(SUM(CASE WHEN direction = 'OUT' THEN boxes ELSE 0 END), 0) as out_boxes,
        COALESCE(SUM(CASE WHEN direction = 'IN' THEN valuation ELSE 0 END), 0) as in_valuation,
        COALESCE(SUM(CASE WHEN direction = 'OUT' THEN valuation ELSE 0 END), 0) as out_valuation,
        COUNT(DISTINCT reception_tracking_number) as active_lots_count
    ")->first();

$inWeight = (float) ($summaryRow->in_weight ?? 0);
$outWeight = (float) ($summaryRow->out_weight ?? 0);
$inBoxes = (float) ($summaryRow->in_boxes ?? 0);
$outBoxes = (float) ($summaryRow->out_boxes ?? 0);
$inValuation = (float) ($summaryRow->in_valuation ?? 0);
$outValuation = (float) ($summaryRow->out_valuation ?? 0);
$daysCount = max(1, Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1);

// Aging
$agingQuery = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->where('remaining_weight', '>', 0);
if (!empty($warehouseIds)) {
    $agingQuery->whereIn('warehouse_id', $warehouseIds);
}
$agingRow = $agingQuery->selectRaw("
    COUNT(DISTINCT reception_tracking_number) as total_lots,
    COUNT(*) as total_items,
    ROUND(SUM(remaining_weight), 2) as total_weight,
    ROUND(SUM(CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END), 2) as total_valuation,
    ROUND(AVG(DATEDIFF(NOW(), date)), 1) as avg_age_days,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN remaining_weight ELSE 0 END), 2) as fresh_weight,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN remaining_weight ELSE 0 END), 2) as optimal_weight,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN remaining_weight ELSE 0 END), 2) as warning_weight,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 90 THEN remaining_weight ELSE 0 END), 2) as alert_weight,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as fresh_val,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as optimal_val,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as warning_val,
    ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 90 THEN CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END ELSE 0 END), 2) as alert_val,
    COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN reception_tracking_number END) as fresh_lots,
    COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN reception_tracking_number END) as optimal_lots,
    COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN reception_tracking_number END) as warning_lots,
    COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 90 THEN reception_tracking_number END) as alert_lots
")->first();

$totalActiveW = (float) ($agingRow->total_weight ?? 1);

$aging = [
    'total_lots' => (int) ($agingRow->total_lots ?? 0),
    'total_weight' => round((float) ($agingRow->total_weight ?? 0), 2),
    'total_valuation' => round((float) ($agingRow->total_valuation ?? 0), 2),
    'avg_age_days' => (float) ($agingRow->avg_age_days ?? 0),
    'buckets' => [
        'fresh' => [
            'label' => '0 - 30 j (Frais)',
            'weight' => (float) ($agingRow->fresh_weight ?? 0),
            'valuation' => (float) ($agingRow->fresh_val ?? 0),
            'lots' => (int) ($agingRow->fresh_lots ?? 0),
            'pct' => round(((float) ($agingRow->fresh_weight ?? 0) / $totalActiveW) * 100, 1),
            'color' => '#00897B'
        ],
        'optimal' => [
            'label' => '31 - 60 j (Normal)',
            'weight' => (float) ($agingRow->optimal_weight ?? 0),
            'valuation' => (float) ($agingRow->optimal_val ?? 0),
            'lots' => (int) ($agingRow->optimal_lots ?? 0),
            'pct' => round(((float) ($agingRow->optimal_weight ?? 0) / $totalActiveW) * 100, 1),
            'color' => '#1E88E5'
        ],
        'warning' => [
            'label' => '61 - 90 j (À surveiller)',
            'weight' => (float) ($agingRow->warning_weight ?? 0),
            'valuation' => (float) ($agingRow->warning_val ?? 0),
            'lots' => (int) ($agingRow->warning_lots ?? 0),
            'pct' => round(((float) ($agingRow->warning_weight ?? 0) / $totalActiveW) * 100, 1),
            'color' => '#FB8C00'
        ],
        'alert' => [
            'label' => '> 90 j (Critique / FIFO)',
            'weight' => (float) ($agingRow->alert_weight ?? 0),
            'valuation' => (float) ($agingRow->alert_val ?? 0),
            'lots' => (int) ($agingRow->alert_lots ?? 0),
            'pct' => round(((float) ($agingRow->alert_weight ?? 0) / $totalActiveW) * 100, 1),
            'color' => '#E53935'
        ],
    ]
];

echo "Aging results ready:\n";
print_r($aging);

