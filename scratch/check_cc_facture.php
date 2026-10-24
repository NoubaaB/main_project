<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::select("SELECT id, is_facture, bill_id, total_weight_production, total_price_production, types_production FROM villamar.congelation_clients WHERE is_facture = 1 LIMIT 3");
echo "=== congelation_clients with is_facture = 1 ===\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
