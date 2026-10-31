<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'FishTypeWap count: ' . \App\Models\FishTypeWap::count() . "\n";
echo 'WarehouseBalance count: ' . \App\Models\WarehouseBalance::count() . "\n";
echo 'WarehouseBalance active in_stock count: ' . \App\Models\WarehouseBalance::where('warehouse_operation', 'stock_in')->whereNull('deleted_at')->where('remaining_weight', '>', 0)->count() . "\n";

