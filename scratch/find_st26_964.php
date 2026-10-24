<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$congelation_clients = DB::select("SELECT id, num_bon_sortie, num_bon_production, num_bon_productions, bill_id, is_facture FROM villamar.congelation_clients WHERE num_bon_production LIKE '%ST26%' OR num_bon_productions LIKE '%ST26%' OR id = 964");
echo "congelation_clients:\n" . json_encode($congelation_clients, JSON_PRETTY_PRINT) . "\n";

$stock_exits = DB::select("SELECT id, num_bon_sortie, bill_id, is_facture FROM villamar.stock_exits WHERE num_bon_sortie LIKE '%ST26-964%' OR num_bon_sortie LIKE '%964%' LIMIT 5");
echo "stock_exits:\n" . json_encode($stock_exits, JSON_PRETTY_PRINT) . "\n";

$bills = DB::select("SELECT id, num_bill, provider_id, types_sortie FROM villamar.bills WHERE types_sortie LIKE '%ST26-964%' OR types_sortie LIKE '%SARDINE ANIMAL%' LIMIT 5");
echo "bills:\n" . json_encode($bills, JSON_PRETTY_PRINT) . "\n";
