<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = new App\Http\Controllers\WarehouseController();
$resp = $c->analytics(new Illuminate\Http\Request());
$data = $resp->getData(true);
print_r($data['aging'] ?? []);

