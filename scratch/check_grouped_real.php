<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Client side:
$vPrice = DB::select("SELECT SUM(total_price_sortie) as p, SUM(total_weight_sortie) as w FROM villamar.ventes WHERE is_facture = 1 AND bill_id > 0")[0];
$cPrice = DB::select("SELECT SUM(total_price_production) as p, SUM(total_weight_production) as w FROM villamar.congelation_clients WHERE is_facture = 1 AND bill_id > 0")[0];
$gPrice = DB::select("SELECT SUM(COALESCE(total_price_sortie_guano_hgt,0) + COALESCE(total_price_sortie_guano_entier,0) + COALESCE(total_price_sortie_guano_filet,0) + COALESCE(total_price_sortie_guano_hg,0)) as p, SUM(COALESCE(total_weight_sortie_guano_hgt,0) + COALESCE(total_weight_sortie_guano_entier,0) + COALESCE(total_weight_sortie_guano_filet,0) + COALESCE(total_weight_sortie_guano_hg,0)) as w FROM villamar.guano_usines WHERE is_facture = 1 AND bill_id > 0")[0];

echo "Client invoiced from ventes: Price = " . number_format($vPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($vPrice->w ?? 0, 2) . " KG\n";
echo "Client invoiced from congelation_clients: Price = " . number_format($cPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($cPrice->w ?? 0, 2) . " KG\n";
echo "Client invoiced from guano_usines: Price = " . number_format($gPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($gPrice->w ?? 0, 2) . " KG\n";

// Supplier side:
$fraisPrice = DB::select("SELECT SUM(total_price_reception) as p, SUM(total_weight_reception) as w FROM villamar.frais WHERE is_facture = 1 AND bill_id > 0")[0];
$cgPrice = DB::select("SELECT SUM(total_price_production) as p, SUM(total_weight_production) as w FROM villamar.congelations WHERE is_facture = 1 AND bill_id > 0")[0];
$cngPrice = DB::select("SELECT SUM(total_price_production) as p, SUM(total_weight_production) as w FROM villamar.congeles WHERE is_facture = 1 AND bill_id > 0")[0];
$afPrice = DB::select("SELECT SUM(total_price_reception) as p, SUM(total_weight_reception) as w FROM villamar.achat_frais WHERE is_facture = 1 AND bill_id > 0")[0];
$guPrice = DB::select("SELECT SUM(total_price_reception_guano) as p, SUM(total_weight_reception_guano) as w FROM villamar.guanos WHERE is_facture = 1 AND bill_id > 0")[0];

echo "\nSupplier invoiced from frais: Price = " . number_format($fraisPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($fraisPrice->w ?? 0, 2) . " KG\n";
echo "Supplier invoiced from congelations: Price = " . number_format($cgPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($cgPrice->w ?? 0, 2) . " KG\n";
echo "Supplier invoiced from congeles: Price = " . number_format($cngPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($cngPrice->w ?? 0, 2) . " KG\n";
echo "Supplier invoiced from achat_frais: Price = " . number_format($afPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($afPrice->w ?? 0, 2) . " KG\n";
echo "Supplier invoiced from guanos: Price = " . number_format($guPrice->p ?? 0, 2) . " MAD, Weight = " . number_format($guPrice->w ?? 0, 2) . " KG\n";
