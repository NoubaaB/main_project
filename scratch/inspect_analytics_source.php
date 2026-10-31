<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$wbCols = Schema::getColumnListing('warehouse_balances');
echo "warehouse_balances columns: " . implode(', ', $wbCols) . "\n";

$aging = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->where('remaining_weight', '>', 0)
    ->selectRaw("
        COUNT(*) as lot_articles,
        SUM(remaining_weight) as total_rem_weight,
        SUM(remaining_package_count) as total_rem_boxes,
        SUM(remaining_weight * unit_price) as total_valuation,
        AVG(DATEDIFF(NOW(), date)) as avg_age_days,
        SUM(CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN remaining_weight ELSE 0 END) as weight_under_30,
        SUM(CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN remaining_weight ELSE 0 END) as weight_30_60,
        SUM(CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN remaining_weight ELSE 0 END) as weight_60_90,
        SUM(CASE WHEN DATEDIFF(NOW(), date) > 90 THEN remaining_weight ELSE 0 END) as weight_over_90
    ")->first();

echo "Aging breakdown:\n";
print_r($aging);
