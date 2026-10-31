<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$types = DB::table('warehouse_balances')
    ->select('stockable_type', DB::raw('count(*) as count'))
    ->groupBy('stockable_type')
    ->get();

echo json_encode($types, JSON_PRETTY_PRINT);

