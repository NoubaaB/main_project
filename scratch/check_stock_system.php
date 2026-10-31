<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$wbCount = DB::table('warehouse_balances')->count();
$wboCount = DB::table('warehouse_balance_operations')->count();
$whWithStock = DB::table('warehouses')->where('total_weight', '>', 0)->count();
$totalStock = DB::table('warehouses')->sum('total_weight');

echo "warehouse_balances count: $wbCount\n";
echo "warehouse_balance_operations count: $wboCount\n";
echo "warehouses with total_weight > 0: $whWithStock\n";
echo "total sum of warehouses.total_weight: $totalStock KG\n";

echo "\nSample 5 warehouse_balances:\n";
print_r(DB::table('warehouse_balances')->limit(5)->get()->toArray());

echo "\nSample 5 warehouse_balance_operations:\n";
print_r(DB::table('warehouse_balance_operations')->limit(5)->get()->toArray());
