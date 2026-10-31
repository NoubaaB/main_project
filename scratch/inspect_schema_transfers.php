<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== warehouse_transfers ===\n";
foreach (DB::select("DESCRIBE warehouse_transfers") as $c) echo "{$c->Field} ({$c->Type})\n";

echo "\n=== detail_warehouse_transfers ===\n";
foreach (DB::select("DESCRIBE detail_warehouse_transfers") as $c) echo "{$c->Field} ({$c->Type})\n";

echo "\n=== inventories ===\n";
foreach (DB::select("DESCRIBE inventories") as $c) echo "{$c->Field} ({$c->Type})\n";

echo "\n=== detail_inventories ===\n";
foreach (DB::select("DESCRIBE detail_inventories") as $c) echo "{$c->Field} ({$c->Type})\n";
