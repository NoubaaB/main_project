<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$f = DB::select("SELECT id, num_bon_reception, types_reception, total_weight_reception, total_price_reception FROM villamar.frais WHERE types_reception IS NOT NULL AND JSON_LENGTH(types_reception) > 0 LIMIT 2");
echo "Sample frais:\n" . json_encode($f, JSON_PRETTY_PRINT) . "\n";
