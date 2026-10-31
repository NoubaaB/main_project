<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$limit = 50;

$t0 = microtime(true);

// Optimized approach:
// 1. Inflows top $limit using index
$inflows = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->selectRaw('
        id as movement_id,
        date as movement_date,
        "IN" as direction,
        source_type,
        source_id,
        reception_tracking_number,
        stockable_type,
        stockable_id,
        warehouse_id,
        total_weight as weight,
        package_count as boxes,
        unit_price,
        total_price,
        supplier_name as partner_name,
        fishery_name,
        origin_name
    ')
    ->orderBy('date', 'desc')
    ->limit($limit)
    ->get();

// 2. Outflows top $limit using index
$outflows = DB::table('warehouse_balance_operations as wbo')
    ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
    ->whereNull('wbo.deleted_at')
    ->whereNull('wb.deleted_at')
    ->selectRaw('
        wbo.id as movement_id,
        wbo.date as movement_date,
        "OUT" as direction,
        wbo.source_type,
        wbo.source_id,
        wb.reception_tracking_number,
        wb.stockable_type,
        wb.stockable_id,
        wb.warehouse_id,
        wbo.weight,
        wbo.package_count as boxes,
        wbo.unit_price,
        wbo.total_price,
        wbo.client_name as partner_name,
        wb.fishery_name,
        wb.origin_name
    ')
    ->orderBy('wbo.date', 'desc')
    ->limit($limit)
    ->get();

// Merge and sort in memory
$merged = $inflows->concat($outflows)->sortByDesc('movement_date')->take($limit)->values();

$duration = round((microtime(true) - $t0) * 1000, 2);
echo "Optimized movements retrieved: " . $merged->count() . " in " . $duration . " ms!\n";
echo "First movement: " . $merged[0]->movement_date . " (" . $merged[0]->direction . ")\n";

