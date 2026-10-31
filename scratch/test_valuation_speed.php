<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WarehouseBalance;
use Illuminate\Support\Facades\DB;

$t0 = microtime(true);
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
$t1 = microtime(true);

echo "SQL sum valuation: " . ($valuationRow->total_valuation ?? 0) . " calculated in " . round(($t1 - $t0) * 1000, 2) . " ms!\n";

