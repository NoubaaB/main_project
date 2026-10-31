<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;

$ctrl = app(WarehouseController::class);

$t0 = microtime(true);
$resp = $ctrl->stockParLot(new Request([
    'status' => 'in_stock',
    'page' => 1,
    'per_page' => 20,
]));
$t1 = microtime(true);
echo "Total stockParLot controller time: " . round(($t1 - $t0) * 1000, 2) . " ms\n";

