<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;

$controller = new WarehouseController();

echo "=== 1. Testing searchTraceabilityVouchers (tracking_numbers: 511, 514) ===\n";
$req1 = new Request([
    'tracking_numbers' => [511, 514],
    'status' => 'all'
]);
$resp1 = $controller->searchTraceabilityVouchers($req1);
$data1 = $resp1->getData(true);
echo "Vouchers count: " . count($data1['vouchers'] ?? []) . "\n";
if (!empty($data1['vouchers'])) {
    foreach (array_slice($data1['vouchers'], 0, 5) as $idx => $v) {
        echo "  [{$idx}] Key: {$v['key']} | Type: {$v['voucher_type']} #{$v['voucher_number']} | Date: {$v['date']} | Status: {$v['status']} | Total: {$v['total_weight']} Kg | Remaining: {$v['remaining_weight']} Kg | Balances: " . count($v['balance_ids']) . "\n";
    }
}

echo "\n=== 2. Testing traceabilityVoucherArticles for first voucher ===\n";
if (!empty($data1['vouchers'])) {
    $first = $data1['vouchers'][0];
    $req2 = new Request([
        'balance_ids' => $first['balance_ids']
    ]);
    $resp2 = $controller->traceabilityVoucherArticles($req2);
    $data2 = $resp2->getData(true);
    echo "Articles count: " . count($data2['articles'] ?? []) . "\n";
    $articleIds = [];
    foreach ($data2['articles'] as $art) {
        $articleIds[] = $art['id'];
        echo "  - Article #{$art['id']}: {$art['article_name']} ({$art['clean_type']}) | Warehouse: {$art['warehouse_name']} | Initial: {$art['initial_weight']} Kg | Remaining: {$art['remaining_weight']} Kg\n";
    }

    echo "\n=== 3. Testing traceabilityPedigreeTimeline ===\n";
    $req3 = new Request([
        'balance_ids' => $articleIds
    ]);
    $resp3 = $controller->traceabilityPedigreeTimeline($req3);
    $data3 = $resp3->getData(true);
    echo "Summary initial weight: " . ($data3['summary']['initial_weight'] ?? 0) . " Kg\n";
    echo "Summary remaining weight: " . ($data3['summary']['remaining_weight'] ?? 0) . " Kg\n";
    echo "Summary exited weight: " . ($data3['summary']['exited_weight'] ?? 0) . " Kg\n";
    echo "Summary consumption: " . ($data3['summary']['consumption_pct'] ?? 0) . "%\n";
    echo "Summary age days: " . ($data3['summary']['age_days'] ?? 0) . " days\n";
    echo "Timeline events count: " . count($data3['timeline'] ?? []) . "\n";
    foreach ($data3['timeline'] as $eidx => $ev) {
        echo "  Event [{$eidx}]: Action: {$ev['event_action']} | Title: {$ev['title']} | Date: {$ev['date']} | Weight: {$ev['weight']} Kg | IsInflow: " . ($ev['is_inflow'] ? '1' : '0') . " | CleanType: " . ($ev['clean_type'] ?? 'null') . "\n";
    }
}
