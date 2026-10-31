<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$t0 = microtime(true);
$countQuery = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->where('reception_tracking_number', '!=', '[]')
    ->where('reception_tracking_number', '!=', '[""]')
    ->selectRaw('
        reception_tracking_number,
        SUM(remaining_weight) as remaining_weight,
        SUM(package_count) as initial_boxes,
        SUM(CASE WHEN remaining_weight > 0 THEN 1 ELSE 0 END) as active_sub_count
    ')
    ->groupBy('reception_tracking_number')
    ->havingRaw('remaining_weight > 0 OR (initial_boxes > 0 AND active_sub_count > 0)');

$totalLots = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
    ->mergeBindings($countQuery)
    ->count();
$t1 = microtime(true);

echo "Optimized count subquery time: " . round(($t1 - $t0) * 1000, 2) . " ms (count: $totalLots)\n";

