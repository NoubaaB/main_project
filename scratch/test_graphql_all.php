<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GraphQLController;
use Illuminate\Http\Request;

$controller = new GraphQLController();

echo "=== 1. Testing stockParLot GraphQL Query ===\n";
$q1 = '
query GetStockParLot {
    stockParLot(status: "in_stock") {
        total_weight
        total_boxes
        total_value
        total_count
        balances {
            id
            article_name
            total_weight
            remaining_weight
            warehouse_name
            status
        }
    }
}';
$res1 = $controller->handle(new Request(['query' => $q1]))->getData(true);
if (isset($res1['errors'])) {
    echo "StockParLot Errors: " . json_encode($res1['errors'], JSON_PRETTY_PRINT) . "\n";
} else {
    $spl = $res1['data']['stockParLot'];
    echo "Total Weight: {$spl['total_weight']} Kg | Count: {$spl['total_count']} | Balances array: " . count($spl['balances']) . "\n";
    if (!empty($spl['balances'])) {
        echo "Sample Balance: " . json_encode($spl['balances'][0]) . "\n";
    }
}

echo "\n=== 2. Testing stockMovementsJournal GraphQL Query ===\n";
$q2 = '
query GetJournal {
    stockMovementsJournal(limit: 5) {
        totals {
            total_count
            total_in_weight
            total_out_weight
            net_weight
        }
        movements {
            id
            date
            type
            action_label
            direction
            article_name
            warehouse_name
            weight
        }
    }
}';
$res2 = $controller->handle(new Request(['query' => $q2]))->getData(true);
if (isset($res2['errors'])) {
    echo "Journal Errors: " . json_encode($res2['errors'], JSON_PRETTY_PRINT) . "\n";
} else {
    $j = $res2['data']['stockMovementsJournal'];
    echo "Journal Totals: " . json_encode($j['totals']) . "\n";
    echo "Journal Movements count: " . count($j['movements']) . "\n";
    if (!empty($j['movements'])) {
        echo "Sample Movement: " . json_encode($j['movements'][0]) . "\n";
    }
}

echo "\n=== 3. Testing warehouses GraphQL Query ===\n";
$q3 = '
query GetWarehousesList {
    warehouses {
        id
        name
        capacity
        category {
            id
            name
        }
    }
}';
$res3 = $controller->handle(new Request(['query' => $q3]))->getData(true);
if (isset($res3['errors'])) {
    echo "Warehouses Errors: " . json_encode($res3['errors'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Warehouses count: " . count($res3['data']['warehouses']) . "\n";
}

