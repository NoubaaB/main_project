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
        'query' => 'query { warehouses { id name } }',
        'vars' => [],
    ],
    'ChambersOverview' => [
        'query' => 'query { chambersOverview { id name active_lots total_weight valuation is_empty } }',
        'vars' => [],
    ],
    'SpeciesOverview' => [
        'query' => 'query { speciesOverview { stockable_id article_name total_weight } }',
        'vars' => [],
    ],
    'StockParLot' => [
        'query' => 'query($status: String, $page: Int, $per_page: Int) { stockParLot(status: $status, page: $page, per_page: $per_page) { total_count summary { total_lots in_stock_lots total_remaining_weight total_valuation } pagination { current_page per_page total last_page } lots { lot_number remaining_weight total_valuation status articles { article_name remaining_weight } } } }',
        'vars' => ['status' => 'in_stock', 'page' => 1, 'per_page' => 20],
    ],
    'StockMovementsJournal' => [
        'query' => 'query($limit: Int) { stockMovementsJournal(limit: $limit) { totals { total_count total_in_weight total_out_weight } movements { id date lot_number direction weight article_name } } }',
        'vars' => ['limit' => 50],
    ],
    'WarehouseAnalytics' => [
        'query' => 'query { warehouseAnalytics { summary { in_weight out_weight net_weight } aging { optimal { lots_count valuation } alert { lots_count valuation } critical { lots_count valuation } } } }',
        'vars' => [],
    ],
    'TraceabilityVouchers' => [
        'query' => 'query($tracking_numbers: [String]) { traceabilityVouchers(tracking_numbers: $tracking_numbers) { key voucher_type voucher_number date remaining_weight tracking_numbers } }',
        'vars' => ['tracking_numbers' => ['511', '715']],
    ],
    'TraceabilityVoucherArticles' => [
        'query' => 'query($balance_ids: [Int]!) { traceabilityVoucherArticles(balance_ids: $balance_ids) { id article_name initial_weight remaining_weight total_price } }',
        'vars' => ['balance_ids' => [1, 2, 3]],
    ],
    'TraceabilityPedigreeTimeline' => [
        'query' => 'query($balance_ids: [Int]!) { traceabilityPedigreeTimeline(balance_ids: $balance_ids) { status summary { tracking_numbers age_days initial_weight remaining_weight } timeline { event_id title date weight } } }',
        'vars' => ['balance_ids' => [1, 2, 3]],
    ],
    'LotTraceability' => [
        'query' => 'query($lot_number: String!) { lotTraceability(lot_number: $lot_number) { lot_number status summary { age_days initial_weight remaining_weight } timeline { event_id date is_inflow weight } articles { id article_name initial_weight remaining_weight } } }',
        'vars' => ['lot_number' => '511'],
    ],
];

echo "=== GraphQL Query Latency & Error Benchmark ===\n";
echo str_repeat('-', 75) . "\n";
printf("%-30s | %12s | %s\n", "Operation", "Latency (ms)", "Status");
echo str_repeat('-', 75) . "\n";

foreach ($queries as $name => $spec) {
    $req = Request::create('/api/graphql', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], json_encode([
        'query' => $spec['query'],
        'variables' => $spec['vars'],
    ]));
    
    $start = microtime(true);
    $res = $controller->handle($req);
    $duration = round((microtime(true) - $start) * 1000, 2);
    $data = $res->getData(true);
    $hasErrors = !empty($data['errors']);
    printf("%-30s | %10.2f ms | %s\n", $name, $duration, $hasErrors ? 'ERROR: ' . json_encode($data['errors']) : 'SUCCESS');
}
echo str_repeat('-', 75) . "\n";
