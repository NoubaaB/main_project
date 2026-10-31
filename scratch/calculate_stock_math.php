<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== STOCK INFLOWS (WEIGHT KG) ===\n";
// 1. frais (congelation production)
$fraisProd = DB::select("SELECT SUM(total_weight_production) as tot FROM villamar.frais")[0]->tot ?? 0;
echo "1. villamar.frais (total_weight_production): " . number_format($fraisProd, 2) . " KG\n";

// 2. congelations
$congelationsProd = DB::select("SELECT SUM(total_weight_production) as tot FROM villamar.congelations")[0]->tot ?? 0;
echo "2. villamar.congelations (total_weight_production): " . number_format($congelationsProd, 2) . " KG\n";

// 3. congeles
$congelesProd = DB::select("SELECT SUM(total_weight_production) as tot FROM villamar.congeles")[0]->tot ?? 0;
echo "3. villamar.congeles (total_weight_production): " . number_format($congelesProd, 2) . " KG\n";

// 4. congelation_clients
$ccProd = DB::select("SELECT SUM(total_weight_production) as tot FROM villamar.congelation_clients")[0]->tot ?? 0;
echo "4. villamar.congelation_clients (total_weight_production): " . number_format($ccProd, 2) . " KG\n";

$totalInflow = $fraisProd + $congelationsProd + $congelesProd + $ccProd;
echo "TOTAL INFLOW: " . number_format($totalInflow, 2) . " KG\n\n";

echo "=== STOCK OUTFLOWS (WEIGHT KG) ===\n";
// 5. ventes
$ventesWeight = DB::select("SELECT SUM(total_weight_sortie) as tot FROM villamar.ventes")[0]->tot ?? 0;
echo "5. villamar.ventes (total_weight_sortie): " . number_format($ventesWeight, 2) . " KG\n";

// 6. stock_exits
$stockExitsWeight = DB::select("SELECT SUM(total_weight_sortie) as tot FROM villamar.stock_exits")[0]->tot ?? 0;
echo "6. villamar.stock_exits (total_weight_sortie): " . number_format($stockExitsWeight, 2) . " KG\n";

$totalOutflow = $ventesWeight + $stockExitsWeight;
echo "TOTAL OUTFLOW: " . number_format($totalOutflow, 2) . " KG\n\n";

$net = $totalInflow - $totalOutflow;
echo "NET STOCK (Inflow - Outflow): " . number_format($net, 2) . " KG\n";

// 7. Chambres current total
$chambresTotal = DB::select("SELECT SUM(total_weight_chambre) as tot FROM villamar.chambres")[0]->tot ?? 0;
echo "CHAMBRES CURRENT RECORDED STOCK: " . number_format($chambresTotal, 2) . " KG\n";

// 8. Stocks current total
$stocksTotal = DB::select("SELECT SUM(total_weight_stock) as tot FROM villamar.stocks")[0]->tot ?? 0;
echo "STOCKS TABLE RECORDED TOTAL: " . number_format($stocksTotal, 2) . " KG\n";

// Difference between Net and Recorded
$diff = $chambresTotal - $net;
echo "DIFFERENCE (Recorded - Net): " . number_format($diff, 2) . " KG\n";
