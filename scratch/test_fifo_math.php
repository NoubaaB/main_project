<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '1024M');
$start = microtime(true);

echo "Testing in-memory FIFO balance matching logic...\n";

// Sample test data
$balances = [
    '13:App\Models\FishType:40' => [
        ['id' => 1, 'date' => '2021-01-02', 'total_weight' => 1000.0, 'remaining_weight' => 1000.0, 'package_count' => 50.0],
        ['id' => 2, 'date' => '2021-01-03', 'total_weight' => 2000.0, 'remaining_weight' => 2000.0, 'package_count' => 100.0],
    ]
];

$withdrawals = [
    ['wh' => 13, 'type' => 'App\Models\FishType', 'id' => 40, 'date' => '2021-01-04', 'weight' => 1500.0, 'pkgs' => 75.0, 'source_id' => 101, 'client' => 'Client A'],
    ['wh' => 13, 'type' => 'App\Models\FishType', 'id' => 40, 'date' => '2021-01-05', 'weight' => 1000.0, 'pkgs' => 50.0, 'source_id' => 102, 'client' => 'Client B'],
];

$operations = [];

foreach ($withdrawals as $w) {
    $key = "{$w['wh']}:{$w['type']}:{$w['id']}";
    $remWeight = $w['weight'];
    $remPkgs = $w['pkgs'];

    if (isset($balances[$key])) {
        foreach ($balances[$key] as &$bal) {
            if ($bal['remaining_weight'] <= 0) continue;

            $takeWeight = min($remWeight, $bal['remaining_weight']);
            $takePkgs = ($bal['total_weight'] > 0) ? round(($takeWeight / $bal['total_weight']) * $bal['package_count'], 2) : 0;

            $bal['remaining_weight'] -= $takeWeight;
            $remWeight -= $takeWeight;
            $remPkgs = max(0, $remPkgs - $takePkgs);

            $operations[] = [
                'warehouse_balance_id' => $bal['id'],
                'date' => $w['date'],
                'weight' => $takeWeight,
                'package_count' => $takePkgs,
                'source_type' => 'DetailShipping',
                'source_id' => $w['source_id'],
                'client_name' => $w['client'],
            ];

            if ($remWeight <= 0) break;
        }
        unset($bal);
    }
}

echo "Created " . count($operations) . " operations.\n";
print_r($operations);
echo "\nUpdated balances:\n";
print_r($balances);
echo "Completed in " . round(microtime(true) - $start, 4) . "s\n";
