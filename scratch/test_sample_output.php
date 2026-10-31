<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\WarehouseController;

$ctrl = new WarehouseController();
$reqPkg = new \Illuminate\Http\Request(['stockable_types' => ['PackageType']]);
$resPkg = $ctrl->traceabilityLots($reqPkg);
$pkgLots = $resPkg->getData()->lots;
echo "Package lots sample:\n";
foreach (array_slice($pkgLots, 0, 3) as $l) {
    echo "  Lot #" . $l->lot_number . " - Types: " . implode(', ', $l->types) . " - " . $l->remaining_weight . " Kg\n";
}

$reqFish = new \Illuminate\Http\Request(['stockable_types' => ['FishType']]);
$resFish = $ctrl->traceabilityLots($reqFish);
$fishLots = $resFish->getData()->lots;
echo "Fish lots sample:\n";
foreach (array_slice($fishLots, 0, 3) as $l) {
    echo "  Lot #" . $l->lot_number . " - Types: " . implode(', ', $l->types) . " - " . $l->remaining_weight . " Kg\n";
}

