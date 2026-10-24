<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$orphanClientBills = DB::select("
    SELECT COUNT(DISTINCT bill_id) as cnt FROM (
        SELECT bill_id FROM villamar.ventes WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bills)
        UNION
        SELECT bill_id FROM villamar.congelation_clients WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bills)
        UNION
        SELECT bill_id FROM villamar.guano_usines WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bills)
    ) as orphans
")[0]->cnt;
echo "Client bill_ids with no row in villamar.bills: {$orphanClientBills}\n";

$orphanSupplierBills = DB::select("
    SELECT COUNT(DISTINCT bill_id) as cnt FROM (
        SELECT bill_id FROM villamar.frais WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bill_providers)
        UNION
        SELECT bill_id FROM villamar.congelations WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bill_providers)
        UNION
        SELECT bill_id FROM villamar.congeles WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bill_providers)
        UNION
        SELECT bill_id FROM villamar.achat_frais WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bill_providers)
        UNION
        SELECT bill_id FROM villamar.guanos WHERE is_facture = 1 AND bill_id > 0 AND bill_id NOT IN (SELECT id FROM villamar.bill_providers)
    ) as orphans
")[0]->cnt;
echo "Supplier bill_ids with no row in villamar.bill_providers: {$orphanSupplierBills}\n";
