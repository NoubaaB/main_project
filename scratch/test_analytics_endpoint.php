<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\WarehouseController;

$ctrl = new WarehouseController();
$req = Request::create('/api/warehouses/analytics', 'GET', [
    'date_from' => '2026-09-01',
    'date_to' => '2026-10-29',
    'granularity' => 'daily'
]);

$start = microtime(true);
$resp = $ctrl->analytics($req);
$time = round((microtime(true) - $start) * 1000, 2);

echo "Status: " . $resp->getStatusCode() . ", Time: {$time}ms\n";
$data = json_decode($resp->getContent(), true);
echo "Summary in_weight: " . $data['summary']['in_weight'] . "\n";
echo "Timeline points: " . count($data['timeline']) . "\n";
echo "Species count: " . count($data['species']) . "\n";
echo "Chambers count: " . count($data['chambers']) . "\n";
echo "Suppliers count: " . count($data['partners']['suppliers']) . "\n";
echo "Clients count: " . count($data['partners']['clients']) . "\n";

