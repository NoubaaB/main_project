<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$q = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->where('reception_tracking_number', '!=', '[]')
    ->where('stockable_type', 'like', '%PackageType%')
    ->distinct()
    ->select('reception_tracking_number')
    ->get();

echo "Distinct PackageType lots: " . $q->count() . "\n";
foreach ($q->take(5) as $r) {
    echo "  Lot: " . $r->reception_tracking_number . "\n";
}

$q2 = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->where('reception_tracking_number', '!=', '[]')
    ->where('stockable_type', 'like', '%GuanoType%')
    ->distinct()
    ->select('reception_tracking_number')
    ->get();

echo "Distinct GuanoType lots: " . $q2->count() . "\n";
foreach ($q2->take(5) as $r) {
    echo "  Lot: " . $r->reception_tracking_number . "\n";
}

$q3 = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->where('reception_tracking_number', '!=', '[]')
    ->where('stockable_type', 'like', '%FishType%')
    ->distinct()
    ->select('reception_tracking_number')
    ->get();

echo "Distinct FishType lots: " . $q3->count() . "\n";

