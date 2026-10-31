<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$t0 = microtime(true);
$inTotals = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->selectRaw('COUNT(*) as total_count, SUM(total_weight) as total_weight, SUM(package_count) as total_boxes')
    ->first();
$t1 = microtime(true);

$outTotals = DB::table('warehouse_balance_operations')
    ->whereNull('deleted_at')
    ->selectRaw('COUNT(*) as total_count, SUM(weight) as total_weight, SUM(package_count) as total_boxes')
    ->first();
$t2 = microtime(true);

echo "In totals: " . round(($t1 - $t0) * 1000, 2) . " ms (count: {$inTotals->total_count}, weight: {$inTotals->total_weight})\n";
echo "Out totals: " . round(($t2 - $t1) * 1000, 2) . " ms (count: {$outTotals->total_count}, weight: {$outTotals->total_weight})\n";
echo "Total duration: " . round(($t2 - $t0) * 1000, 2) . " ms\n";

