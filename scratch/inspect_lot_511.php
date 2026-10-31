<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WarehouseBalance;
use App\Models\Charge;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

echo "=== CHARGES WITH 511 ===\n";
$charges = Charge::where('reception_tracking_number', 'like', '%511%')->get();
foreach ($charges as $c) {
    $lotStr = is_array($c->reception_tracking_number) ? json_encode($c->reception_tracking_number) : $c->reception_tracking_number;
    echo "Charge ID: {$c->id}, Date: {$c->date}, Lot: {$lotStr}\n";
}

echo "=== PRODUCTIONS WITH 511 ===\n";
$prods = Production::where('reception_tracking_number', 'like', '%511%')->get();
foreach ($prods as $p) {
    $lotStr = is_array($p->reception_tracking_number) ? json_encode($p->reception_tracking_number) : $p->reception_tracking_number;
    echo "Production ID: {$p->id}, Date: {$p->date}, Lot: {$lotStr}\n";
}

echo "=== WAREHOUSE BALANCES WITH 511 ===\n";
$wbs = WarehouseBalance::where('warehouse_operation', 'stock_in')
    ->where('reception_tracking_number', 'like', '%511%')
    ->whereNull('deleted_at')
    ->get();
echo "Found " . $wbs->count() . " stock_in balances with 511:\n";

// Group them by source voucher / date / lot
$vouchers = [];
foreach ($wbs as $b) {
    $src = class_basename($b->source_type);
    $key = $b->date ? $b->date->format('Y-m-d') : 'no-date';
    $vKey = "{$src}_{$b->source_id}_{$key}";
    if (!isset($vouchers[$vKey])) {
        $vouchers[$vKey] = [
            'source_type' => $src,
            'source_id' => $b->source_id,
            'date' => $key,
            'lot' => $b->reception_tracking_number,
            'supplier' => $b->supplier_name,
            'warehouse' => $b->warehouse->name ?? ('#' . $b->warehouse_id),
            'articles' => [],
            'total_weight' => 0,
        ];
    }
    $vouchers[$vKey]['articles'][] = [
        'id' => $b->id,
        'article_name' => $b->article_name,
        'type' => $b->clean_stockable_type,
        'weight' => (float)$b->total_weight,
        'rem_weight' => (float)$b->remaining_weight,
        'boxes' => (float)$b->package_count,
        'operations_count' => $b->operations->count(),
    ];
    $vouchers[$vKey]['total_weight'] += (float)$b->total_weight;
}

foreach ($vouchers as $k => $v) {
    echo "\n--- VOUCHER: {$v['source_type']} #{$v['source_id']} | Date: {$v['date']} | Lot: {$v['lot']} | Supplier: {$v['supplier']} | Total W: {$v['total_weight']} Kg ---\n";
    foreach ($v['articles'] as $art) {
        echo "   Article: {$art['article_name']} ({$art['type']}) - Init: {$art['weight']} Kg, Rem: {$art['rem_weight']} Kg, Ops: {$art['operations_count']}\n";
    }
}
