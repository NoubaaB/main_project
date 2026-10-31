<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$dateFrom = '2026-09-01';
$dateTo = '2026-10-29';

// Test aging
$agingQuery = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->where('remaining_weight', '>', 0)
    ->selectRaw("
        COUNT(DISTINCT reception_tracking_number) as total_lots,
        COUNT(*) as total_items,
        ROUND(SUM(remaining_weight), 2) as total_weight,
        ROUND(SUM(CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END), 2) as total_valuation,
        ROUND(AVG(DATEDIFF(NOW(), date)), 1) as avg_age_days,
        ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN remaining_weight ELSE 0 END), 2) as fresh_weight,
        ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN remaining_weight ELSE 0 END), 2) as optimal_weight,
        ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN remaining_weight ELSE 0 END), 2) as warning_weight,
        ROUND(SUM(CASE WHEN DATEDIFF(NOW(), date) > 90 THEN remaining_weight ELSE 0 END), 2) as alert_weight,
        COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) <= 30 THEN reception_tracking_number END) as fresh_lots,
        COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 30 AND DATEDIFF(NOW(), date) <= 60 THEN reception_tracking_number END) as optimal_lots,
        COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 60 AND DATEDIFF(NOW(), date) <= 90 THEN reception_tracking_number END) as warning_lots,
        COUNT(DISTINCT CASE WHEN DATEDIFF(NOW(), date) > 90 THEN reception_tracking_number END) as alert_lots
    ")->first();

print_r($agingQuery);

