<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GraphQLController;
use Illuminate\Http\Request;

$controller = app(GraphQLController::class);

$queries = [
    'Warehouses' => [
        'query' => 'query { warehouses { id name capacity category { id name } } }',
        'variables' => [],
    ],
    'ChambersOverview' => [
        'query' => 'query { chambersOverview { id name active_lots total_weight valuation is_empty } }',
        'variables' => [],
    ],
    'SpeciesOverview' => [
        'query' => 'query { speciesOverview { stockable_id article_name total_weight total_boxes wap total_valuation } }',
        'variables' => [],
    ],
    'StockParLot' => [
        'query' => 'query($status: String, $page: Int, $per_page: Int) { stockParLot(status: $status, page: $page, per_page: $per_page) { total_count summary { total_lots in_stock_lots total_remaining_weight total_valuation } pagination { current_page per_page total last_page } lots { lot_number remaining_weight total_valuation status articles { article_name remaining_weight } } } }',
        'variables' => ['status' => 'in_stock', 'page' => 1, 'per_page' => 3],
    ],
    'StockMovementsJournal' => [
        'query' => 'query($limit: Int) { stockMovementsJournal(limit: $limit) { totals { total_count total_in_weight total_out_weight } movements { id date lot_number direction weight article_name } } }',
        'variables' => ['limit' => 5],
    ],
    'WarehouseAnalytics' => [
        'query' => 'query { warehouseAnalytics { summary { in_weight out_weight net_weight } aging { optimal { lots_count valuation } alert { lots_count valuation } critical { lots_count valuation } } } }',
        'variables' => [],
    ],
    'TraceabilityVouchers' => [
        'query' => 'query($tracking_numbers: [String]) { traceabilityVouchers(tracking_numbers: $tracking_numbers) { key voucher_type voucher_number date remaining_weight tracking_numbers } }',
        'variables' => ['tracking_numbers' => ['511']],
    ],
    'LotTraceability' => [
        'query' => 'query($lot_number: String!) { lotTraceability(lot_number: $lot_number) { lot_number status summary { age_days initial_weight remaining_weight } timeline { event_id date is_inflow weight } articles { id article_name initial_weight remaining_weight } } }',
        'variables' => ['lot_number' => '511'],
    ],
];

echo "=== Testing All GraphQL Queries via GraphQLController ===\n";

foreach ($queries as $name => $spec) {
    $req = new Request([], [], [], [], [], [], json_encode([
        'query' => $spec['query'],
        'variables' => $spec['variables'],
    ]));
    $req->headers->set('Content-Type', 'application/json');

    $res = $controller->handle($req);
    $data = $res->getData(true);

    if (!empty($data['errors'])) {
        echo "❌ {$name} FAILED:\n" . json_encode($data['errors'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "✅ {$name} SUCCESS\n";
    }
}

echo "=== All GraphQL tests complete! ===\n";

