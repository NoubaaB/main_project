<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WarehouseController;

$t0 = microtime(true);

$limit = 50;
$movementType = 'all';

// Base Inflows query
$inBase = DB::table('warehouse_balances as wb')
    ->where('wb.warehouse_operation', 'stock_in')
    ->whereNull('wb.deleted_at');

// Base Outflows query
$outBase = DB::table('warehouse_balance_operations as wbo')
    ->join('warehouse_balances as wb', 'wb.id', '=', 'wbo.warehouse_balance_id')
    ->whereNull('wbo.deleted_at')
    ->whereNull('wb.deleted_at');

// 1. Separate fast totals
$inWeight = 0; $outWeight = 0; $inBoxes = 0; $outBoxes = 0; $totalCount = 0;
if ($movementType !== 'out') {
    $inStats = (clone $inBase)->selectRaw('COUNT(*) as total_count, COALESCE(SUM(total_weight), 0) as total_weight, COALESCE(SUM(package_count), 0) as total_boxes')->first();
    $inWeight = (float) ($inStats->total_weight ?? 0);
    $inBoxes = (float) ($inStats->total_boxes ?? 0);
    $totalCount += (int) ($inStats->total_count ?? 0);
}
if ($movementType !== 'in') {
    $outStats = (clone $outBase)->selectRaw('COUNT(*) as total_count, COALESCE(SUM(wbo.weight), 0) as total_weight, COALESCE(SUM(wbo.package_count), 0) as total_boxes')->first();
    $outWeight = (float) ($outStats->total_weight ?? 0);
    $outBoxes = (float) ($outStats->total_boxes ?? 0);
    $totalCount += (int) ($outStats->total_count ?? 0);
}

// 2. Fetch rows
$inRows = collect([]);
if ($movementType !== 'out') {
    $inRows = (clone $inBase)
        ->selectRaw('
            wb.id as movement_id,
            wb.date as movement_date,
            "IN" as direction,
            wb.source_type,
            wb.source_id,
            wb.reception_tracking_number,
            wb.stockable_type,
            wb.stockable_id,
            wb.warehouse_id,
            wb.total_weight as weight,
            wb.package_count as boxes,
            wb.unit_price,
            wb.total_price,
            wb.supplier_name as partner_name,
            wb.fishery_name,
            wb.origin_name
        ')
        ->orderBy('wb.date', 'desc')
        ->limit($limit)
        ->get();
}

$outRows = collect([]);
if ($movementType !== 'in') {
    $outRows = (clone $outBase)
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
}

$combined = $inRows->concat($outRows)->sortByDesc('movement_date')->take($limit)->values();

$t1 = microtime(true);

echo "New execution time: " . round(($t1 - $t0) * 1000, 2) . " ms\n";
echo "Total count: " . $totalCount . " (inWeight: $inWeight, outWeight: $outWeight)\n";
echo "Combined rows: " . $combined->count() . "\n";
echo "Top row date: " . $combined[0]->movement_date . "\n";

