<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Find bill in villamar.bill_providers
$bills = DB::select("SELECT * FROM villamar.bill_providers WHERE num_bill LIKE '%00260905%' OR id = 1477");
echo "=== villamar.bill_providers ===\n";
echo json_encode($bills, JSON_PRETTY_PRINT) . "\n\n";

if (!empty($bills)) {
    $bid = $bills[0]->id;
    echo "=== villamar.frais for bill_id $bid ===\n";
    $frais = DB::select("SELECT id, date_reception, types_reception, total_weight_reception, total_price_reception FROM villamar.frais WHERE bill_id = $bid");
    echo json_encode($frais, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== villamar.achat_frais for bill_id $bid ===\n";
    $af = DB::select("SELECT id, date_reception, types_reception, total_weight_reception, total_price_reception FROM villamar.achat_frais WHERE bill_id = $bid");
    echo json_encode($af, JSON_PRETTY_PRINT) . "\n\n";
}

