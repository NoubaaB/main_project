<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'charges', 'detail_charges',
    'productions', 'detail_productions',
    'shippings', 'detail_shippings',
    'warehouse_transfers', 'detail_warehouse_transfers',
    'inventories', 'detail_inventories',
    'warehouse_balances', 'warehouse_balance_operations',
    'warehouses',
];

foreach ($tables as $t) {
    echo "=== Table: {$t} ===\n";
    $cols = DB::select("DESCRIBE {$t}");
    foreach ($cols as $c) {
        if (preg_match('/(tracking|lot|warehouse|stock|date|weight|price|box|package)/i', $c->Field)) {
            echo "  {$c->Field} ({$c->Type})\n";
        }
    }
}
