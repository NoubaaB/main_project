<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;

$ctrl = app(WarehouseController::class);

$t0 = microtime(true);
$resp = $ctrl->stockMovementsJournal(new Request(['limit' => 50]));
$t1 = microtime(true);

$data = $resp->getData(true);
echo "Old execution time: " . round(($t1 - $t0) * 1000, 2) . " ms\n";
echo "Total movements: " . ($data['totals']['total_count'] ?? 0) . "\n";
echo "Movements count returned: " . count($data['movements'] ?? []) . "\n";

