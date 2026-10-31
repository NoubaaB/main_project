<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '1024M');
$start = microtime(true);

echo "=== Testing Transfer Migration Logic ===\n";
$transfers = DB::select("SELECT * FROM villamar.stock_movement_logs WHERE deleted_at IS NULL LIMIT 10");
echo "Fetched " . count($transfers) . " sample transfers.\n";
foreach ($transfers as $t) {
    echo "Transfer {$t->id}: Date {$t->date}, From {$t->from_chambre_id} To {$t->to_chambre_id}\n";
    $typesProd = json_decode($t->types_production ?? '[]', true) ?: [];
    echo "   types_production items: " . count($typesProd) . "\n";
}

echo "\n=== Testing Inventory Migration Logic ===\n";
$logs = DB::select("SELECT * FROM villamar.stock_logs WHERE deleted_at IS NULL LIMIT 10");
echo "Fetched " . count($logs) . " sample stock logs.\n";
foreach ($logs as $l) {
    echo "Log {$l->id}: Date {$l->date}, Wh {$l->chambre_id}, Type {$l->type_id}, New {$l->qte_new}, Old {$l->qte_old}\n";
}

echo "\nTest logic verified in " . round(microtime(true) - $start, 3) . "s\n";
