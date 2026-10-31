<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GraphQLController;
use Illuminate\Http\Request;

$controller = new GraphQLController();

echo "=== 1. Testing GraphQL Query: chambersOverview & speciesOverview ===\n";
$query = '
query GetWarehouseOverview {
    chambersOverview {
        id
        name
        total_weight
        active_lots
        lots_count
        valuation
        total_value
        is_empty
    }
    speciesOverview {
        stockable_id
        article_name
        species_name
        family_name
        process_name
        total_weight
        total_valuation
        lots_count
    }
}';

$req = new Request(['query' => $query]);
$resp = $controller->handle($req);
$data = $resp->getData(true);

if (isset($data['errors'])) {
    echo "Errors: " . json_encode($data['errors'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Chambers count: " . count($data['data']['chambersOverview'] ?? []) . "\n";
    echo "Species count: " . count($data['data']['speciesOverview'] ?? []) . "\n";
    echo "Sample Chamber: " . json_encode($data['data']['chambersOverview'][0]) . "\n";
    echo "Sample Species: " . json_encode($data['data']['speciesOverview'][0]) . "\n";
}

echo "\n=== 2. Testing GraphQL Query: traceabilityVouchers & analytics ===\n";
$query2 = '
query GetTraceabilityAndAnalytics($lots: [String]) {
    traceabilityVouchers(tracking_numbers: $lots) {
        key
        voucher_type
        voucher_number
        total_weight
        status
        tracking_numbers
    }
    warehouseAnalytics(granularity: "month") {
        granularity
        summary {
            in_weight
            out_weight
            net_weight
            movements_count
            active_lots_count
        }
        ratios {
            turnover_ratio_weight
        }
        aging {
            fresh { weight lots_count }
            optimal { weight lots_count }
            warning { weight lots_count }
            critical { weight lots_count }
        }
    }
}';

$req2 = new Request([
    'query' => $query2,
    'variables' => ['lots' => ['511', '514']]
]);
$resp2 = $controller->handle($req2);
$data2 = $resp2->getData(true);

if (isset($data2['errors'])) {
    echo "Errors: " . json_encode($data2['errors'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Traceability Vouchers count: " . count($data2['data']['traceabilityVouchers'] ?? []) . "\n";
    echo "Analytics summary: " . json_encode($data2['data']['warehouseAnalytics']['summary'] ?? []) . "\n";
    echo "Analytics aging: " . json_encode($data2['data']['warehouseAnalytics']['aging'] ?? []) . "\n";
}
