<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::select("SELECT id, num_bon_production, num_bon_reception, types_production, total_weight_production, total_price_production, date_production, is_facture, bill_id FROM villamar.congelations WHERE bill_id = 1477");
echo "Congelations with bill_id = 1477:\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n\n";

$chg = DB::select("SELECT c.id, c.date, c.total_weight, c.total_price, c.invoice_id FROM charges c WHERE c.invoice_id = 1477");
echo "Charges with invoice_id = 1477 in Jupiter:\n" . json_encode($chg, JSON_PRETTY_PRINT) . "\n\n";

if (!empty($chg)) {
    $dc = DB::select("SELECT dc.id, dc.charge_id, dc.stockable_type, dc.stockable_id, dc.weight, dc.unit_price, dc.total_price FROM detail_charges dc WHERE dc.charge_id IN (" . implode(',', array_column($chg, 'id')) . ")");
    echo "Detail charges for invoice 1477 in Jupiter:\n" . json_encode($dc, JSON_PRETTY_PRINT) . "\n";
}
