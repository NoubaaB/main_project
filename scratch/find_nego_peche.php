<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$sup = DB::select("SELECT id, name_provider FROM villamar.providers WHERE name_provider LIKE '%NEGO%' OR name_provider LIKE '%PECHE%'");
echo "Suppliers matching NEGO PECHE:\n" . json_encode($sup, JSON_PRETTY_PRINT) . "\n\n";

$inv = DB::select("SELECT id, num_bill, date_bill, provider_id, types_sortie FROM villamar.bill_providers WHERE num_bill LIKE '%00260905%' OR num_bill LIKE '%00260905.1%' OR num_bill LIKE '%0905%'");
echo "Bill providers matching 00260905:\n" . json_encode($inv, JSON_PRETTY_PRINT) . "\n\n";
