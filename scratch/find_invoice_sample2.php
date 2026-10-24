<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::select("SELECT id, num_bill, date_bill, provider_id, types_sortie FROM villamar.bills WHERE num_bill LIKE '%002600094%' OR num_bill LIKE '%00094%' OR num_bill LIKE '%0026%' LIMIT 5");
echo "Bills matching:\n" . json_encode($b, JSON_PRETTY_PRINT) . "\n\n";

$b2 = DB::select("SELECT id, num_bill, date_bill, provider_id, types_sortie FROM villamar.bills WHERE types_sortie LIKE '%ST26-964%' LIMIT 1");
echo "Bills with ST26-964:\n" . json_encode($b2, JSON_PRETTY_PRINT) . "\n\n";
