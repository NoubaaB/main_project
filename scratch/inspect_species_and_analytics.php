<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = new App\Http\Controllers\WarehouseController();

$resp1 = $c->speciesOverview(new Illuminate\Http\Request());
$data1 = $resp1->getData(true);
echo "=== Species Overview first item ===\n";
print_r($data1['species'][0] ?? []);

$resp2 = $c->analytics(new Illuminate\Http\Request());
$data2 = $resp2->getData(true);
echo "\n=== Analytics keys ===\n";
print_r(array_keys($data2));
echo "\n=== Analytics kpis ===\n";
print_r($data2['kpis'] ?? ($data2['summary'] ?? []));

