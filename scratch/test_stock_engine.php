<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '1024M');
$startTime = microtime(true);

echo "Testing stock engine simulation...\n";

// 1. Check stock_in rows from detail_productions
$dpIn = DB::table('detail_productions')
    ->where('warehouse_operation', 'stock_in')
    ->whereNotNull('warehouse_id')
    ->where('weight', '>', 0)
    ->count();
echo "detail_productions stock_in: $dpIn\n";

// 2. Check stock_in rows from detail_charges
$dcIn = DB::table('detail_charges')
    ->where('warehouse_operation', 'stock_in')
    ->whereNotNull('warehouse_id')
    ->where('weight', '>', 0)
    ->count();
echo "detail_charges stock_in: $dcIn\n";

// 3. Check stock_out rows from detail_shippings
$dsOut = DB::table('detail_shippings')
    ->where('warehouse_operation', 'stock_out')
    ->whereNotNull('warehouse_id')
    ->where('weight', '>', 0)
    ->count();
echo "detail_shippings stock_out: $dsOut\n";

echo "Check completed in " . round(microtime(true) - $startTime, 2) . "s\n";
