<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== STOCK & WAREHOUSE AUDIT ===\n";

$wbCount = DB::table('warehouse_balances')->count();
$wboCount = DB::table('warehouse_balance_operations')->count();
$finishedWb = DB::table('warehouse_balances')->where('is_finished', 1)->count();
$activeWb = DB::table('warehouse_balances')->where('is_finished', 0)->count();

echo "Warehouse Balances Total: {$wbCount} (Active: {$activeWb}, Finished/Exhausted: {$finishedWb})\n";
echo "Warehouse Balance Operations (FIFO): {$wboCount}\n\n";

$wtCount = DB::table('warehouse_transfers')->count();
$dwtCount = DB::table('detail_warehouse_transfers')->count();
echo "Warehouse Transfers: {$wtCount} (Details: {$dwtCount})\n";

$invCount = DB::table('inventories')->count();
$dinvCount = DB::table('detail_inventories')->count();
echo "Inventories: {$invCount} (Details: {$dinvCount})\n\n";

echo "=== TOP WAREHOUSES BY ACTIVE STOCK ===\n";
$whs = DB::table('warehouses')->orderByDesc('total_weight')->limit(10)->get();
foreach ($whs as $w) {
    $fmtWeight = number_format($w->total_weight, 2);
    echo "ID {$w->id}: {$w->name} | Total Weight: {$fmtWeight} KG\n";
}

$sumWh = DB::table('warehouses')->sum('total_weight');
$sumActiveBal = DB::table('warehouse_balances')->where('is_finished', 0)->sum('remaining_weight');
echo "\nTotal Warehouses Stock: " . number_format($sumWh, 2) . " KG\n";
echo "Total Active Balances Remaining Weight: " . number_format($sumActiveBal, 2) . " KG\n";
