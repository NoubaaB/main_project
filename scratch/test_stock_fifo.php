<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$start = microtime(true);

// Count stock_in candidates
$prodIn = DB::table('detail_productions')->where('warehouse_operation', 'stock_in')->whereNotNull('warehouse_id')->where('weight', '>', 0)->count();
echo "detail_productions stock_in count: $prodIn\n";

$chargeIn = DB::table('detail_charges')->where('warehouse_operation', 'stock_in')->whereNotNull('warehouse_id')->where('weight', '>', 0)->count();
echo "detail_charges stock_in count: $chargeIn\n";

// Count stock_out candidates
$shipOut = DB::table('detail_shippings')->where('warehouse_operation', 'stock_out')->whereNotNull('warehouse_id')->where('weight', '>', 0)->count();
echo "detail_shippings stock_out count: $shipOut\n";

$time = round(microtime(true) - $start, 2);
echo "Count check completed in {$time}s\n";
