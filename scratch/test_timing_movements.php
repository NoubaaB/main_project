<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$t0 = microtime(true);
$inflowsCount = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->count();
$t1 = microtime(true);
echo "Inflows count: $inflowsCount in " . round(($t1 - $t0) * 1000, 2) . " ms\n";

$outflowsCount = DB::table('warehouse_balance_operations')
    ->whereNull('deleted_at')
    ->count();
$t2 = microtime(true);
echo "Outflows count: $outflowsCount in " . round(($t2 - $t1) * 1000, 2) . " ms\n";

