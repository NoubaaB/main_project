<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$fishTypesMap = DB::table('fish_types')->pluck('name_in_invoice', 'id')->toArray();
$packageTypesMap = DB::table('package_types')->pluck('name_in_invoice', 'id')->toArray();
$trucksMap = DB::table('trucks')->pluck('id', 'registration_plate')->toArray();

echo "Testing Transfer and Inventory migration logic...\n";

// 1. Transfers
$smlRows = DB::select("SELECT * FROM villamar.stock_movement_logs WHERE deleted_at IS NULL");
echo "Found " . count($smlRows) . " active stock_movement_logs.\n";

$transfersBatch = [];
$detailTransfersBatch = [];
$transferTruckBatch = [];

foreach ($smlRows as $sml) {
    $trackingBons = [];
    if (!empty($sml->num_bon_transfers)) {
        $arr = json_decode($sml->num_bon_transfers, true);
        if (is_array($arr)) {
            foreach ($arr as $b) if (!empty($b)) $trackingBons[] = trim((string)$b);
        }
    }

    $plates = [];
    if (!empty($sml->matricules)) {
        $arr = json_decode($sml->matricules, true);
        if (is_array($arr)) {
            foreach ($arr as $p) if (!empty($p) && $p !== 'N/A') $plates[] = trim((string)$p);
        }
    }

    $firstTruckId = null;
    foreach ($plates as $pl) {
        if (!isset($trucksMap[$pl])) {
            $newId = DB::table('trucks')->insertGetId([
                'registration_plate' => $pl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $trucksMap[$pl] = $newId;
        }
        $tId = $trucksMap[$pl];
        if (!$firstTruckId) $firstTruckId = $tId;
        $transferTruckBatch[] = [
            'warehouse_transfer_id' => $sml->id,
            'truck_id' => $tId,
        ];
    }

    $items = json_decode($sml->types_production ?? '[]', true) ?: [];
    $totalW = 0;

    foreach ($items as $it) {
        $w = (float)($it['weight'] ?? 0);
        $totalW += $w;
        $tId = !empty($it['type_id']) ? (int)$it['type_id'] : null;
        $cId = !empty($it['container_id']) ? (int)$it['container_id'] : null;
        $nbr = (float)($it['nbr'] ?? 0);
        $fromWh = !empty($sml->from_chambre_id) ? (int)$sml->from_chambre_id : 13;
        $toWh = !empty($sml->to_chambre_id) ? (int)$sml->to_chambre_id : 14;

        $detailTransfersBatch[] = [
            'warehouse_transfer_id' => $sml->id,
            'warehouse_transfer_category_id' => 1,
            'stockable_type' => 'App\\Models\\FishType',
            'stockable_id' => $tId,
            'weight' => $w,
            'source_warehouse_id' => $fromWh,
            'source_tracking_number' => $it['num_bon_reception'] ?? null,
            'destination_warehouse_id' => $toWh,
            'package_type_id' => $cId,
            'nbr_box' => $nbr,
            'truck_id' => $firstTruckId,
            'created_at' => $sml->created_at ?? now(),
            'updated_at' => $sml->updated_at ?? now(),
        ];
    }

    $transfersBatch[] = [
        'id' => $sml->id,
        'date' => $sml->date ?? '2021-01-01',
        'total_weight' => $totalW,
        'transfer_tracking_number' => json_encode($trackingBons),
        'description' => "Transfert de chambre {$sml->from_chambre_id} vers chambre {$sml->to_chambre_id}",
        'created_at' => $sml->created_at ?? now(),
        'updated_at' => $sml->updated_at ?? now(),
    ];
}

echo "Prepared " . count($transfersBatch) . " transfers and " . count($detailTransfersBatch) . " detail transfers.\n";

// 2. Inventories
$stockLogs = DB::select("SELECT * FROM villamar.stock_logs WHERE deleted_at IS NULL");
echo "Found " . count($stockLogs) . " active stock_logs.\n";

// Group stock_logs by date and chambre_id to create inventories
$groupedLogs = [];
foreach ($stockLogs as $sl) {
    $d = $sl->date ?? '2021-01-01';
    $wh = !empty($sl->chambre_id) ? (int)$sl->chambre_id : 13;
    $key = "{$d}:{$wh}";
    $groupedLogs[$key][] = $sl;
}

echo "Grouped into " . count($groupedLogs) . " distinct inventories.\n";

$invBatch = [];
$detailInvBatch = [];
$invId = 0;

foreach ($groupedLogs as $key => $logs) {
    $invId++;
    [$d, $wh] = explode(':', $key);
    $totalW = 0;

    foreach ($logs as $sl) {
        $qNew = (float)($sl->qte_new ?? 0);
        $qOld = (float)($sl->qte_old ?? 0);
        $diff = $qNew - $qOld;
        $w = abs($diff);
        if ($w <= 0) $w = abs($qNew);
        if ($w <= 0) continue;

        $op = ($diff >= 0) ? 'stock_in' : 'stock_out';
        $tId = is_numeric($sl->type_id) ? (int)$sl->type_id : null;
        $cId = !empty($sl->container_id) ? (int)$sl->container_id : null;
        $desc = ($tId && isset($fishTypesMap[$tId])) ? $fishTypesMap[$tId] : ($sl->type === 'Emballage' ? 'Emballage' : 'Poisson');

        $totalW += $w;

        $detailInvBatch[] = [
            'inventory_id' => $invId,
            'detail_inventory_category_id' => 1,
            'article_description' => $desc,
            'stockable_type' => ($sl->type === 'Emballage' ? 'App\\Models\\PackageType' : 'App\\Models\\FishType'),
            'stockable_id' => $tId,
            'weight' => $w,
            'unit_price' => 0,
            'total_price' => 0,
            'package_type_id' => $cId,
            'nbr_box' => 0,
            'warehouse_id' => $wh,
            'warehouse_operation' => $op,
            'created_at' => $sl->created_at ?? now(),
            'updated_at' => $sl->updated_at ?? now(),
        ];
    }

    $invBatch[] = [
        'id' => $invId,
        'date' => $d,
        'inventory_tracking_number' => json_encode(["INV-{$d}-WH{$wh}"]),
        'description' => "Inventaire physique chambre {$wh} au {$d}",
        'total_weight' => $totalW,
        'total_price' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

echo "Prepared " . count($invBatch) . " inventories and " . count($detailInvBatch) . " detail inventories.\n";
