<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

$cacheKey = 'wh_summary_test';

$t0 = microtime(true);
$summary = Cache::remember($cacheKey, 60, function () {
    $stats = DB::table('warehouse_balances')
        ->where('warehouse_operation', 'stock_in')
        ->whereNull('deleted_at')
        ->whereNotNull('reception_tracking_number')
        ->selectRaw('
            COUNT(DISTINCT reception_tracking_number) as total_lots,
            COUNT(DISTINCT CASE WHEN remaining_weight > 0 THEN reception_tracking_number END) as in_stock_lots,
            COUNT(DISTINCT CASE WHEN remaining_weight <= 0 THEN reception_tracking_number END) as depleted_lots,
            ROUND(SUM(total_weight), 2) as total_initial_weight,
            ROUND(SUM(remaining_weight), 2) as total_remaining_weight,
            ROUND(SUM(package_count), 2) as total_initial_boxes,
            COUNT(DISTINCT warehouse_id) as occupied_chambers
        ')->first();

    $valuation = DB::table('warehouse_balances')
        ->where('warehouse_operation', 'stock_in')
        ->whereNull('deleted_at')
        ->where('remaining_weight', '>', 0)
        ->selectRaw('
            ROUND(SUM(CASE 
                WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight 
                ELSE COALESCE(unit_price, 0) * remaining_weight 
            END), 2) as total_valuation
        ')->first();

    return [
        'stats' => $stats,
        'valuation' => (float) ($valuation->total_valuation ?? 0),
    ];
});
$t1 = microtime(true);

echo "First run (with DB query): " . round(($t1 - $t0) * 1000, 2) . " ms\n";

$t2 = microtime(true);
$summary2 = Cache::get($cacheKey);
$t3 = microtime(true);
echo "Second run (from Cache): " . round(($t3 - $t2) * 1000, 2) . " ms\n";

