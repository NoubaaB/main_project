<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GraphQLController;
use Illuminate\Http\Request;

$controller = new GraphQLController();

echo "=== 1. Testing GraphQL Query: traceabilityVouchers & articles ===\n";
$query = '
query GetVouchersAndArticles($lots: [String]) {
    traceabilityVouchers(tracking_numbers: $lots) {
        key
        voucher_type
        voucher_number
        balance_ids
    }
}';

$req = new Request([
    'query' => $query,
    'variables' => ['lots' => ['511', '514']]
]);
$resp = $controller->handle($req);
$data = $resp->getData(true);

if (isset($data['errors'])) {
    echo "Errors: " . json_encode($data['errors'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$vouchers = $data['data']['traceabilityVouchers'] ?? [];
echo "Vouchers found: " . count($vouchers) . "\n";
$first = $vouchers[0] ?? null;
if ($first && !empty($first['balance_ids'])) {
    echo "First voucher balance_ids: " . json_encode($first['balance_ids']) . "\n";

    echo "\n=== 2. Testing traceabilityVoucherArticles with balance_ids ===\n";
    $query2 = '
    query GetArticles($balance_ids: [Int]!) {
        traceabilityVoucherArticles(balance_ids: $balance_ids) {
            id
            article_name
            clean_type
            initial_weight
            remaining_weight
        }
    }';

    $req2 = new Request([
        'query' => $query2,
        'variables' => ['balance_ids' => $first['balance_ids']]
    ]);
    $resp2 = $controller->handle($req2);
    $data2 = $resp2->getData(true);
    if (isset($data2['errors'])) {
        echo "Errors in articles: " . json_encode($data2['errors'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Articles: " . json_encode($data2['data']['traceabilityVoucherArticles'] ?? []) . "\n";
    }

    echo "\n=== 3. Testing traceabilityPedigreeTimeline with balance_ids ===\n";
    $query3 = '
    query GetTimeline($balance_ids: [Int]!) {
        traceabilityPedigreeTimeline(balance_ids: $balance_ids) {
            status
            summary {
                tracking_numbers
                initial_weight
                remaining_weight
                age_days
            }
            timeline {
                event_action
                title
                date
                weight
                is_inflow
            }
        }
    }';

    $req3 = new Request([
        'query' => $query3,
        'variables' => ['balance_ids' => $first['balance_ids']]
    ]);
    $resp3 = $controller->handle($req3);
    $data3 = $resp3->getData(true);
    if (isset($data3['errors'])) {
        echo "Errors in timeline: " . json_encode($data3['errors'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Timeline status: " . ($data3['data']['traceabilityPedigreeTimeline']['status'] ?? '') . "\n";
        echo "Timeline summary: " . json_encode($data3['data']['traceabilityPedigreeTimeline']['summary'] ?? []) . "\n";
        echo "Timeline events count: " . count($data3['data']['traceabilityPedigreeTimeline']['timeline'] ?? []) . "\n";
    }
}

