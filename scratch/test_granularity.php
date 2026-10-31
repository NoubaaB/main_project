<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dateFrom = '2026-01-01';
$dateTo = '2026-10-29';

$inflowsQuery = DB::table('warehouse_balances as wb')
    ->where('wb.warehouse_operation', 'stock_in')
    ->whereNull('wb.deleted_at')
    ->whereBetween('wb.date', [$dateFrom, $dateTo])
    ->selectRaw('
        wb.id,
        wb.date,
        "IN" as direction,
        wb.total_weight as weight,
        COALESCE(wb.total_price, wb.total_weight * wb.unit_price) as valuation
    ');

$outflowsQuery = DB::table('warehouse_balance_operations as wbo')
    ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
    ->whereNull('wbo.deleted_at')
    ->whereNull('wb.deleted_at')
    ->whereBetween('wbo.date', [$dateFrom, $dateTo])
    ->selectRaw('
        wbo.id,
        wbo.date,
        "OUT" as direction,
        wbo.weight,
        COALESCE(wbo.total_price, wbo.weight * wbo.unit_price) as valuation
    ');

$union = $inflowsQuery->unionAll($outflowsQuery);

// Monthly test
$monthly = DB::table(DB::raw("({$union->toSql()}) as u"))
    ->mergeBindings($union)
    ->groupBy('period_label')
    ->orderBy('period_label', 'asc')
    ->selectRaw("
        DATE_FORMAT(date, '%Y-%m-01') as period_label,
        SUM(CASE WHEN direction = 'IN' THEN weight ELSE 0 END) as in_weight,
        SUM(CASE WHEN direction = 'OUT' THEN weight ELSE 0 END) as out_weight
    ")->get();

echo "Monthly points count: " . count($monthly) . "\n";
foreach ($monthly->take(5) as $m) {
    echo "  {$m->period_label}: IN={$m->in_weight}, OUT={$m->out_weight}\n";
}

