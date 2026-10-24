<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- BILLS SCHEMA ---\n";
foreach (DB::select('DESCRIBE villamar.bills') as $c) {
    echo $c->Field . ' (' . $c->Type . ")\n";
}

echo "\n--- BILL_PROVIDERS SCHEMA ---\n";
foreach (DB::select('DESCRIBE villamar.bill_providers') as $c) {
    echo $c->Field . ' (' . $c->Type . ")\n";
}
