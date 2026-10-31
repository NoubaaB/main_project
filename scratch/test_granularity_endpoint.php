<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\WarehouseController;

$ctrl = new WarehouseController();
$req = Request::create('/api/warehouses/analytics', 'GET', [
    'date_from' => '2026-01-01',
    'date_to' => '2026-10-29',
    'granularity' => 'monthly'
]);

$resp = $ctrl->analytics($req);
$data = json_decode($resp->getContent(), true);
echo "Monthly timeline count: " . count($data['timeline']) . "\n";
echo "Aging total lots: " . $data['aging']['total_lots'] . "\n";
echo "Ratios destocking_rate: " . $data['ratios']['destocking_rate'] . "%\n";
echo "Families count: " . count($data['families']) . "\n";
foreach ($data['families'] as $f) {
    echo "  {$f['label']}: flow={$f['total_flow']}, share={$f['share_pct']}%\n";
}

