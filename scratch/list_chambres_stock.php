<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$chambres = DB::select("SELECT id, name_chambre, num_chambre, stock_id, total_weight_chambre FROM villamar.chambres WHERE total_weight_chambre > 0 ORDER BY total_weight_chambre DESC");
echo "Chambres with total_weight_chambre > 0 (" . count($chambres) . " chambres):\n";
$total = 0;
foreach ($chambres as $c) {
    echo "ID: " . str_pad($c->id, 4) . " | " . str_pad($c->num_chambre, 8) . " | Stock ID: {$c->stock_id} | " . str_pad(number_format($c->total_weight_chambre, 2) . " KG", 18) . " | Name: {$c->name_chambre}\n";
    $total += $c->total_weight_chambre;
}
echo "TOTAL: " . number_format($total, 2) . " KG\n";
