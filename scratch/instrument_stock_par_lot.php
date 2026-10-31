<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WarehouseController;

$t0 = microtime(true);

$query = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number')
    ->where('reception_tracking_number', '!=', '')
    ->where('reception_tracking_number', '!=', '[]')
    ->where('reception_tracking_number', '!=', '[""]');

$gcSuppliers = WarehouseController::groupConcat('supplier_name', ', ');
$gcFisheries = WarehouseController::groupConcat('fishery_name', ', ');
$gcOrigins = WarehouseController::groupConcat('origin_name', ', ');
$gcWhIds = WarehouseController::groupConcat('warehouse_id', ',');
$gcSourceTypes = WarehouseController::groupConcat('source_type', ',');

$query->selectRaw("
    reception_tracking_number,
    COUNT(*) as articles_count,
    MIN(date) as first_date,
    MAX(date) as last_date,
    ROUND(SUM(total_weight), 2) as initial_weight,
    ROUND(SUM(remaining_weight), 2) as remaining_weight,
    ROUND(SUM(package_count), 2) as initial_boxes,
    ROUND(SUM(CASE WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight ELSE unit_price * remaining_weight END), 2) as estimated_valuation,
    {$gcSuppliers} as suppliers,
    {$gcFisheries} as fisheries,
    {$gcOrigins} as origins,
    {$gcWhIds} as wh_ids_str,
    {$gcSourceTypes} as source_types_str,
    SUM(CASE WHEN remaining_weight > 0 THEN 1 ELSE 0 END) as active_sub_count
")->groupBy('reception_tracking_number');

$query->havingRaw('remaining_weight > 0 OR (initial_boxes > 0 AND active_sub_count > 0)');

$t1 = microtime(true);
$totalLots = DB::table(DB::raw("({$query->toSql()}) as sub"))
    ->mergeBindings($query)
    ->count();
$t2 = microtime(true);
echo "1. count subquery time: " . round(($t2 - $t1) * 1000, 2) . " ms (totalLots: $totalLots)\n";

$paginatedLots = $query->orderBy('first_date', 'desc')->skip(0)->take(20)->get();
$t3 = microtime(true);
echo "2. paginated lots query time: " . round(($t3 - $t2) * 1000, 2) . " ms (lots fetched: " . $paginatedLots->count() . ")\n";

$rawLotKeys = $paginatedLots->pluck('reception_tracking_number')->toArray();
$balancesQuery = \App\Models\WarehouseBalance::with([
    'stockable' => function ($morphTo) {
        $morphTo->morphWith([
            \App\Models\FishType::class => ['fish_family', 'fish_process_type'],
        ]);
    },
    'warehouse',
    'packageType',
    'operations' => fn($q) => $q->orderBy('date', 'desc'),
])
->whereIn('reception_tracking_number', $rawLotKeys)
->where('warehouse_operation', 'stock_in')
->whereNull('deleted_at');

$lotBalances = $balancesQuery->orderBy('date', 'desc')->get()->groupBy('reception_tracking_number');
$t4 = microtime(true);
echo "3. Eloquent hydrate 20 lots: " . round(($t4 - $t3) * 1000, 2) . " ms\n";

$summaryQuery = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->whereNotNull('reception_tracking_number');

$summaryStats = $summaryQuery->selectRaw('
    COUNT(DISTINCT reception_tracking_number) as total_lots,
    COUNT(DISTINCT CASE WHEN remaining_weight > 0 THEN reception_tracking_number END) as in_stock_lots,
    COUNT(DISTINCT CASE WHEN remaining_weight <= 0 THEN reception_tracking_number END) as depleted_lots,
    ROUND(SUM(total_weight), 2) as total_initial_weight,
    ROUND(SUM(remaining_weight), 2) as total_remaining_weight,
    ROUND(SUM(package_count), 2) as total_initial_boxes,
    COUNT(DISTINCT warehouse_id) as occupied_chambers
')->first();
$t5 = microtime(true);
echo "4. summaryStats query time: " . round(($t5 - $t4) * 1000, 2) . " ms\n";

$valuationRow = DB::table('warehouse_balances')
    ->where('warehouse_operation', 'stock_in')
    ->whereNull('deleted_at')
    ->where('remaining_weight', '>', 0)
    ->selectRaw('
        ROUND(SUM(CASE 
            WHEN total_price > 0 AND total_weight > 0 THEN (total_price / total_weight) * remaining_weight 
            ELSE COALESCE(unit_price, 0) * remaining_weight 
        END), 2) as total_valuation
    ')->first();
$t6 = microtime(true);
echo "5. valuationRow query time: " . round(($t6 - $t5) * 1000, 2) . " ms\n";

