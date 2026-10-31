<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\GraphQL\AppResolver;
use App\GraphQL\AppSchema;
use GraphQL\GraphQL;

echo "Building AppSchema...\n";
$schema = AppSchema::build(true);
$rootValue = AppResolver::getRootValue();
echo "AppSchema built successfully!\n";

$queries = [
    'Warehouse (Existing)' => 'query { warehouses { id name } }',
    '1. Productions' => 'query { productions(date_start: "2026-01-01", date_end: "2026-12-31") { id date total_weight } }',
    '1b. Production Analytics' => 'query { productionAnalytics(date_start: "2026-10-01", date_end: "2026-10-31", mode: "daily") { fresh_reception { x y } } }',
    '2. Charges' => 'query { charges(date_start: "2026-01-01", date_end: "2026-12-31") { id date reception_tracking_number } }',
    '3. Shippings' => 'query { shippings(date_start: "2026-01-01", date_end: "2026-12-31") { id date shipping_tracking_number } }',
    '4. Supplier Invoices' => 'query { supplierInvoices(date_start: "2026-01-01", date_end: "2026-12-31") { id date tracking_numbers } }',
    '5a. Supplier Payments' => 'query { supplierPayments(year: 2026) { id date amount } }',
    '5b. Supplier Balance' => 'query { supplierBalance(year: 2026) { carry_forward_total balance { id name } } }',
    '5c. Supplier Year Summary' => 'query { supplierYearSummary(year: 2026) { summary { id name total_invoices } } }',
    '6. Client Invoices' => 'query { clientInvoices(date_start: "2026-01-01", date_end: "2026-12-31") { id date tracking_numbers } }',
    '7a. Client Payments' => 'query { clientPayments(year: 2026) { id date amount } }',
    '7b. Client Balance' => 'query { clientBalance(year: 2026) { carry_forward_total balance { id name } } }',
    '7c. Client Year Summary' => 'query { clientYearSummary(year: 2026) { summary { id name total_invoices } } }',
    '8. Order Transfers' => 'query { orderTransfers(date_start: "2026-01-01", date_end: "2026-12-31") { id date amount transfer_number } }',
    '9. Inventories' => 'query { inventories(date_start: "2026-01-01", date_end: "2026-12-31") { id date inventory_tracking_numbers } }',
    '10. User Statistics' => 'query { userStatistics(date_start: "2026-10-01", date_end: "2026-10-31") { date_range { start end } today_stats { total active_users } } }',
];

$allPassed = true;
foreach ($queries as $name => $query) {
    $t0 = microtime(true);
    $result = GraphQL::executeQuery($schema, $query, $rootValue);
    $t1 = microtime(true);
    $arr = $result->toArray();
    $duration = round(($t1 - $t0) * 1000, 1);
    if (!empty($arr['errors'])) {
        echo "[FAIL] $name: " . json_encode($arr['errors']) . "\n";
        $allPassed = false;
    } else {
        echo "[PASS] $name ({$duration}ms)\n";
    }
}

if ($allPassed) {
    echo "\nALL 10 MODULE GRAPHQL QUERIES PASSED!\n";
} else {
    echo "\nSOME QUERIES FAILED!\n";
    exit(1);
}
