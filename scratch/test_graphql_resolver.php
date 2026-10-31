<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;

$schemaString = <<<GQL
type Query {
    chambersOverview: [ChamberOverview]
    traceabilityVouchers(tracking_numbers: [String], status: String): [TraceabilityVoucher]
}

type ChamberOverview {
    id: ID!
    name: String
    category_name: String
    total_weight: Float
    total_boxes: Float
    total_value: Float
    lots_count: Int
    is_empty: Boolean
}

type TraceabilityVoucher {
    key: String!
    source_type: String
    parent_id: Int
    voucher_type: String
    voucher_number: String
    date: String
    supplier_name: String
    total_weight: Float
    remaining_weight: Float
    exited_weight: Float
    total_boxes: Float
    status: String
    consumption_pct: Float
    articles_count: Int
    articles_preview: [String]
    article_types: [String]
    tracking_numbers: [String]
    balance_ids: [Int]
}
GQL;

$rootValue = [
    'chambersOverview' => function($root, $args) {
        $controller = app(WarehouseController::class);
        $resp = $controller->chambersOverview(new Request($args));
        $data = $resp->getData(true);
        return $data['chambers'] ?? [];
    },
    'traceabilityVouchers' => function($root, $args) {
        $controller = app(WarehouseController::class);
        $resp = $controller->searchTraceabilityVouchers(new Request($args));
        $data = $resp->getData(true);
        return $data['vouchers'] ?? [];
    }
];

$schema = BuildSchema::build($schemaString);

$query1 = '{
    chambersOverview {
        id
        name
        total_weight
        lots_count
    }
}';

$res1 = GraphQL::executeQuery($schema, $query1, $rootValue);
$out1 = $res1->toArray();

echo "Query 1 (chambersOverview) count: " . count($out1['data']['chambersOverview'] ?? []) . "\n";
if (!empty($out1['data']['chambersOverview'])) {
    echo "First chamber: " . json_encode($out1['data']['chambersOverview'][0]) . "\n";
}

$query2 = '{
    traceabilityVouchers(tracking_numbers: ["511", "514"]) {
        key
        voucher_type
        voucher_number
        total_weight
        remaining_weight
        tracking_numbers
    }
}';

$res2 = GraphQL::executeQuery($schema, $query2, $rootValue);
$out2 = $res2->toArray();
echo "Query 2 (traceabilityVouchers) count: " . count($out2['data']['traceabilityVouchers'] ?? []) . "\n";
if (!empty($out2['data']['traceabilityVouchers'])) {
    echo "First voucher: " . json_encode($out2['data']['traceabilityVouchers'][0]) . "\n";
}

