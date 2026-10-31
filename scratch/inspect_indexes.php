<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$indexesWb = DB::select("SHOW INDEX FROM warehouse_balances");
echo "warehouse_balances indexes:\n";
foreach ($indexesWb as $idx) {
    echo "  {$idx->Key_name} on {$idx->Column_name}\n";
}

$indexesWbo = DB::select("SHOW INDEX FROM warehouse_balance_operations");
echo "warehouse_balance_operations indexes:\n";
foreach ($indexesWbo as $idx) {
    echo "  {$idx->Key_name} on {$idx->Column_name}\n";
}

