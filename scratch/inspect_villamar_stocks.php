<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Count of stock-related tables in villamar ===\n";
echo "stock_movement_logs: " . DB::select("SELECT count(*) as cnt FROM villamar.stock_movement_logs")[0]->cnt . "\n";
echo "stock_logs: " . DB::select("SELECT count(*) as cnt FROM villamar.stock_logs")[0]->cnt . "\n";
echo "history_stocks: " . DB::select("SELECT count(*) as cnt FROM villamar.history_stocks")[0]->cnt . "\n";
echo "chambres: " . DB::select("SELECT count(*) as cnt FROM villamar.chambres WHERE total_weight_chambre > 0")[0]->cnt . " with total_weight_chambre > 0\n";

echo "\n=== Sum of total_weight_chambre in villamar.chambres ===\n";
$sumChambres = DB::select("SELECT SUM(total_weight_chambre) as total FROM villamar.chambres")[0]->total;
echo "SUM(total_weight_chambre): " . number_format($sumChambres, 2) . " KG\n";

echo "\n=== Sum of total_weight_stock in villamar.stocks ===\n";
$stocks = DB::select("SELECT id, name_stock, total_weight_stock FROM villamar.stocks");
foreach ($stocks as $s) {
    echo "Stock {$s->id} ({$s->name_stock}): " . number_format($s->total_weight_stock, 2) . " KG\n";
}

echo "\n=== Sample 3 stock_movement_logs ===\n";
print_r(DB::select("SELECT id, date, from_chambre_id, to_chambre_id, qte_transfer, type_id, container_id, types_production, types_sortie FROM villamar.stock_movement_logs WHERE deleted_at IS NULL LIMIT 3"));

echo "\n=== Sample 3 history_stocks ===\n";
print_r(DB::select("SELECT * FROM villamar.history_stocks LIMIT 3"));

echo "\n=== Sample 3 stock_logs ===\n";
print_r(DB::select("SELECT * FROM villamar.stock_logs LIMIT 3"));
