<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\GraphQL\WarehouseSchema;
use App\GraphQL\WarehouseResolver;
use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;

$sdl = WarehouseSchema::getSDL();
$schema = BuildSchema::build($sdl);
$rootValue = WarehouseResolver::getRootValue();

$query = '
query TestStockParLot {
  stockParLot(status: "in_stock", page: 1, per_page: 5) {
    total_count
    total_weight
    total_value
    summary {
      total_lots
      in_stock_lots
      total_remaining_weight
      total_valuation
    }
    pagination {
      current_page
      per_page
      total
      last_page
    }
    lots {
      lot_number
      supplier_name
      remaining_weight
      remaining_boxes
      total_valuation
      status
      articles {
        article_name
        clean_type
        remaining_weight
      }
    }
  }
}
';

$result = GraphQL::executeQuery($schema, $query, $rootValue);
$output = $result->toArray();

if (!empty($output['errors'])) {
    echo "ERRORS:\n" . json_encode($output['errors'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "SUCCESS! Returned lots: " . count($output['data']['stockParLot']['lots']) . "\n";
echo "Summary: " . json_encode($output['data']['stockParLot']['summary']) . "\n";
echo "First lot: " . json_encode($output['data']['stockParLot']['lots'][0] ?? null) . "\n";

