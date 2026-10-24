<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$cc = DB::select("SELECT id, num_bon_production, types_production, total_weight_production, total_price_production FROM villamar.congelation_clients WHERE types_production IS NOT NULL LIMIT 3");
echo "Sample congelation_clients:\n" . json_encode($cc, JSON_PRETTY_PRINT) . "\n";
