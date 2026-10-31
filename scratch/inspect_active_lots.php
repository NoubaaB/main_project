<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$sampleBalances = DB::table('warehouse_balances')
    ->where('remaining_weight', '>', 0)
    ->limit(10)
    ->get();

echo "=== Sample Active Balances (Stock par Lot) ===\n";
foreach ($sampleBalances as $b) {
    echo "ID: {$b->id} | Lot: '{$b->reception_tracking_number}' | WH: {$b->warehouse_id} | Type: {$b->stockable_type} #{$b->stockable_id} | Init: {$b->total_weight} Kg | Rem: {$b->remaining_weight} Kg | Pkgs: {$b->package_count} | Source: {$b->source_type} #{$b->source_id} | Date: {$b->date}\n";
}

$lotCounts = DB::table('warehouse_balances')
    ->selectRaw("COUNT(*) as total_batches, COUNT(DISTINCT reception_tracking_number) as distinct_lots, SUM(remaining_weight) as total_rem_kg, SUM(package_count) as total_boxes")
    ->where('remaining_weight', '>', 0)
    ->first();

echo "\nActive Lots Summary:\n";
echo "Distinct Active Lots: {$lotCounts->distinct_lots}\n";
echo "Active Batches: {$lotCounts->total_batches}\n";
echo "Total Active Weight: " . number_format($lotCounts->total_rem_kg, 2) . " Kg\n";
echo "Total Active Boxes: " . number_format($lotCounts->total_boxes, 2) . "\n";
