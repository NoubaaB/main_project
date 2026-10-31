<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\WarehouseController;

$ctrl = new WarehouseController();
$reqAll = new \Illuminate\Http\Request(['stockable_types' => []]);
$resAll = $ctrl->traceabilityLots($reqAll);
echo "Total lots without filter: " . count($resAll->getData()->lots) . "\n";

$reqFish = new \Illuminate\Http\Request(['stockable_types' => ['FishType']]);
$resFish = $ctrl->traceabilityLots($reqFish);
echo "Fish lots count: " . count($resFish->getData()->lots) . "\n";

$reqGuano = new \Illuminate\Http\Request(['stockable_types' => ['GuanoType']]);
$resGuano = $ctrl->traceabilityLots($reqGuano);
echo "Guano lots count: " . count($resGuano->getData()->lots) . "\n";

$reqPkg = new \Illuminate\Http\Request(['stockable_types' => ['PackageType']]);
$resPkg = $ctrl->traceabilityLots($reqPkg);
echo "Package lots count: " . count($resPkg->getData()->lots) . "\n";

$reqMulti = new \Illuminate\Http\Request(['stockable_types' => ['GuanoType', 'PackageType']]);
$resMulti = $ctrl->traceabilityLots($reqMulti);
echo "Guano + Package lots count: " . count($resMulti->getData()->lots) . "\n";

