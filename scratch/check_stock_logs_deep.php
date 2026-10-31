<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== stock_logs schema ===\n";
foreach (DB::select("DESCRIBE villamar.stock_logs") as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== stock_logs sample with types_detail ===\n";
$withDetails = DB::select("SELECT id, date, qte_new, qte_old, type_id, container_id, chambre_id, type, types_detail FROM villamar.stock_logs WHERE types_detail IS NOT NULL AND types_detail != '[]' AND types_detail != '' LIMIT 5");
print_r($withDetails);

echo "\n=== stock_logs sample without types_detail ===\n";
$withoutDetails = DB::select("SELECT id, date, qte_new, qte_old, type_id, container_id, chambre_id, type FROM villamar.stock_logs WHERE (types_detail IS NULL OR types_detail = '[]' OR types_detail = '') AND deleted_at IS NULL LIMIT 5");
print_r($withoutDetails);
