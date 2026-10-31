<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '1024M');
$start = microtime(true);

echo "Testing complete warehouse sync algorithm on current DB...\n";

// A. detail_productions (stock_in)
$prodIns = DB::select("
    SELECT 
        dp.id as source_id,
        'DetailProduction' as source_type,
        dp.warehouse_id,
        dp.stockable_type,
        dp.stockable_id,
        dp.package_type_id,
        dp.nbr_box as package_count,
        dp.weight,
        dp.unit_price,
        dp.total_price,
        p.date,
        p.production_tracking_number as tracking_num,
        COALESCE(s.name, c.name, 'Production') as entity_name
    FROM detail_productions dp
    JOIN productions p ON p.id = dp.production_id
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN clients c ON c.id = p.client_id
    WHERE dp.warehouse_operation = 'stock_in' AND dp.warehouse_id IS NOT NULL AND dp.weight > 0
");
echo "Loaded " . count($prodIns) . " detail_productions stock_in.\n";

// B. detail_shippings (stock_out)
$shipOuts = DB::select("
    SELECT 
        ds.id as source_id,
        'DetailShipping' as source_type,
        ds.warehouse_id,
        ds.stockable_type,
        ds.stockable_id,
        ds.package_type_id,
        ds.nbr_box as package_count,
        ds.weight,
        ds.unit_price,
        ds.total_price,
        s.date,
        COALESCE(c.name, 'Client') as entity_name
    FROM detail_shippings ds
    JOIN shippings s ON s.id = ds.shipping_id
    LEFT JOIN clients c ON c.id = s.client_id
    WHERE ds.warehouse_operation = 'stock_out' AND ds.warehouse_id IS NOT NULL AND ds.weight > 0
");
echo "Loaded " . count($shipOuts) . " detail_shippings stock_out.\n";

$stockInItems = $prodIns;
usort($stockInItems, fn($a, $b) => strcmp($a->date, $b->date) ?: ($a->source_id <=> $b->source_id));

$wbBatch = [];
$balanceId = 0;
$activeBalanceMap = [];

foreach ($stockInItems as $item) {
    $balanceId++;
    $whId = (int)$item->warehouse_id;
    $sType = $item->stockable_type;
    $sId = !empty($item->stockable_id) ? (int)$item->stockable_id : 1;
    $w = (float)$item->weight;
    $pkgs = (float)($item->package_count ?? 0);
    $key = "{$whId}:{$sType}:{$sId}";

    $balRecord = [
        'id' => $balanceId,
        'warehouse_id' => $whId,
        'stockable_type' => $sType,
        'stockable_id' => $sId,
        'reception_tracking_number' => is_string($item->tracking_num) ? $item->tracking_num : json_encode($item->tracking_num),
        'warehouse_operation' => 'stock_in',
        'date' => $item->date,
        'total_weight' => $w,
        'remaining_weight' => $w,
        'total_price' => (float)($item->total_price ?? 0),
        'unit_price' => (float)($item->unit_price ?? 0),
        'package_count' => $pkgs,
        'package_type_id' => $item->package_type_id,
        'supplier_name' => $item->entity_name ?? null,
        'source_type' => $item->source_type,
        'source_id' => $item->source_id,
        'is_finished' => 0,
    ];

    $wbBatch[] = $balRecord;

    $activeBalanceMap[$key][] = [
        'id' => $balanceId,
        'date' => $item->date,
        'total_weight' => $w,
        'remaining_weight' => $w,
        'package_count' => $pkgs,
        'unit_price' => (float)($item->unit_price ?? 0),
    ];
}

$stockOutItems = $shipOuts;
usort($stockOutItems, fn($a, $b) => strcmp($a->date, $b->date) ?: ($a->source_id <=> $b->source_id));

$operationsBatch = [];
$totalOps = 0;

foreach ($stockOutItems as $out) {
    $whId = (int)$out->warehouse_id;
    $sType = $out->stockable_type;
    $sId = !empty($out->stockable_id) ? (int)$out->stockable_id : 1;
    $key = "{$whId}:{$sType}:{$sId}";
    $remWeight = (float)$out->weight;
    $remPkgs = (float)($out->package_count ?? 0);
    $uPrice = (float)($out->unit_price ?? 0);

    if (isset($activeBalanceMap[$key])) {
        foreach ($activeBalanceMap[$key] as &$bal) {
            if ($bal['remaining_weight'] <= 0) continue;

            $takeW = min($remWeight, $bal['remaining_weight']);
            $takePkgs = ($bal['total_weight'] > 0) ? round(($takeW / $bal['total_weight']) * $bal['package_count'], 2) : 0;

            $bal['remaining_weight'] -= $takeW;
            $remWeight -= $takeW;
            $remPkgs = max(0, $remPkgs - $takePkgs);

            $operationsBatch[] = [
                'warehouse_balance_id' => $bal['id'],
                'date' => $out->date,
                'weight' => $takeW,
                'unit_price' => $uPrice,
                'total_price' => round($takeW * $uPrice, 2),
                'package_count' => $takePkgs,
                'source_type' => $out->source_type,
                'source_id' => $out->source_id,
                'client_name' => $out->entity_name ?? null,
            ];

            $totalOps++;
            if ($remWeight <= 0) break;
        }
        unset($bal);
    }

    if ($remWeight > 0) {
        $balanceId++;
        $initBal = [
            'id' => $balanceId,
            'warehouse_id' => $whId,
            'stockable_type' => $sType,
            'stockable_id' => $sId,
            'reception_tracking_number' => json_encode(['STOCK-INIT']),
            'warehouse_operation' => 'stock_in',
            'date' => $out->date,
            'total_weight' => $remWeight,
            'remaining_weight' => 0,
            'total_price' => 0,
            'unit_price' => $uPrice,
            'package_count' => $remPkgs,
            'package_type_id' => $out->package_type_id,
            'supplier_name' => 'Stock Initial / Antérieur',
            'source_type' => 'InitialStock',
            'source_id' => null,
            'is_finished' => 1,
        ];
        $wbBatch[] = $initBal;

        $operationsBatch[] = [
            'warehouse_balance_id' => $balanceId,
            'date' => $out->date,
            'weight' => $remWeight,
            'unit_price' => $uPrice,
            'total_price' => round($remWeight * $uPrice, 2),
            'package_count' => $remPkgs,
            'source_type' => $out->source_type,
            'source_id' => $out->source_id,
            'client_name' => $out->entity_name ?? null,
        ];
        $totalOps++;
    }
}

$duration = round(microtime(true) - $start, 2);
echo "FIFO simulated in {$duration}s!\n";
echo "Total balances generated: " . count($wbBatch) . "\n";
echo "Total operations generated: {$totalOps}\n";
