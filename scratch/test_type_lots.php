<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$guanoLots = DB::table('warehouse_balances')
    ->where('stockable_type', 'like', '%GuanoType%')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->select('reception_tracking_number')
    ->distinct()
    ->limit(5)
    ->pluck('reception_tracking_number');

$pkgLots = DB::table('warehouse_balances')
    ->where('stockable_type', 'like', '%PackageType%')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->select('reception_tracking_number')
    ->distinct()
    ->limit(5)
    ->pluck('reception_tracking_number');

$fishLots = DB::table('warehouse_balances')
    ->where('stockable_type', 'like', '%FishType%')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->select('reception_tracking_number')
    ->distinct()
    ->limit(5)
    ->pluck('reception_tracking_number');

echo "Fish Lots: " . json_encode($fishLots) . "\n";
echo "Guano Lots: " . json_encode($guanoLots) . "\n";
echo "Package Lots: " . json_encode($pkgLots) . "\n";

