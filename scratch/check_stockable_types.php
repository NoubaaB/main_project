<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- DETAIL_CHARGES SAMPLE ---\n";
$dc = DB::select("SELECT id, charge_id, stockable_type, stockable_id, weight, total_price FROM detail_charges LIMIT 5");
foreach ($dc as $r) {
    echo "ID: {$r->id}, charge_id: {$r->charge_id}, stockable_type: '{$r->stockable_type}', stockable_id: " . ($r->stockable_id ?? 'NULL') . ", weight: {$r->weight}, price: {$r->total_price}\n";
}

$dcNull = DB::select("SELECT count(*) as cnt FROM detail_charges WHERE stockable_id IS NULL")[0]->cnt;
$dcNotNull = DB::select("SELECT count(*) as cnt FROM detail_charges WHERE stockable_id IS NOT NULL")[0]->cnt;
echo "Detail charges with null stockable_id: {$dcNull}, not null: {$dcNotNull}\n";

$typesInDc = DB::select("SELECT DISTINCT stockable_type FROM detail_charges");
echo "Distinct stockable_types in detail_charges: " . json_encode(array_column($typesInDc, 'stockable_type')) . "\n";

echo "\n--- DETAIL_PRODUCTIONS SAMPLE ---\n";
$dp = DB::select("SELECT id, production_id, stockable_type, stockable_id, weight, total_price FROM detail_productions LIMIT 5");
foreach ($dp as $r) {
    echo "ID: {$r->id}, prod_id: {$r->production_id}, stockable_type: '{$r->stockable_type}', stockable_id: " . ($r->stockable_id ?? 'NULL') . "\n";
}

$typesInDp = DB::select("SELECT DISTINCT stockable_type FROM detail_productions");
echo "Distinct stockable_types in detail_productions: " . json_encode(array_column($typesInDp, 'stockable_type')) . "\n";

echo "\n--- DETAIL_SHIPPINGS SAMPLE ---\n";
$ds = DB::select("SELECT id, shipping_id, stockable_type, stockable_id, weight, total_price FROM detail_shippings LIMIT 5");
foreach ($ds as $r) {
    echo "ID: {$r->id}, ship_id: {$r->shipping_id}, stockable_type: '{$r->stockable_type}', stockable_id: " . ($r->stockable_id ?? 'NULL') . "\n";
}

$typesInDs = DB::select("SELECT DISTINCT stockable_type FROM detail_shippings");
echo "Distinct stockable_types in detail_shippings: " . json_encode(array_column($typesInDs, 'stockable_type')) . "\n";
