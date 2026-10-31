<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = new App\Http\Controllers\WarehouseController();
$resp = $c->chambersOverview(new Illuminate\Http\Request());
$data = $resp->getData(true);
print_r(array_slice($data['chambers'], 0, 2));

