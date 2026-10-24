<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::select("SELECT * FROM villamar.bills WHERE num_bill = '002600094' OR id = 2600094 OR id = 94");
echo "Bills matching 002600094:\n" . json_encode($b, JSON_PRETTY_PRINT) . "\n\n";

$bp = DB::select("SELECT * FROM villamar.bill_providers WHERE num_bill = '002600094' OR id = 2600094 OR id = 94");
echo "Bill providers matching 002600094:\n" . json_encode($bp, JSON_PRETTY_PRINT) . "\n\n";

$v = DB::select("SELECT * FROM villamar.ventes WHERE num_bon_sortie = 'ST26-964' OR num_bon_sortie LIKE '%964%' LIMIT 3");
echo "Ventes matching ST26-964:\n" . json_encode($v, JSON_PRETTY_PRINT) . "\n\n";

$cc = DB::select("SELECT * FROM villamar.congelation_clients WHERE num_bon_production = 'ST26-964' OR num_bon_production LIKE '%964%' LIMIT 3");
echo "Congelation clients matching ST26-964:\n" . json_encode($cc, JSON_PRETTY_PRINT) . "\n\n";
