<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Distinct types / actions in stock_logs ===\n";
print_r(DB::select("SELECT type, count(*) as cnt FROM villamar.stock_logs GROUP BY type"));

echo "\n=== Distinct dates in stock_logs ===\n";
print_r(DB::select("SELECT date, count(*) as cnt FROM villamar.stock_logs GROUP BY date ORDER BY date DESC LIMIT 10"));

echo "\n=== Chambres with positive stock in villamar.chambres ===\n";
$chambresStock = DB::select("SELECT id, name_chambre, num_chambre, total_weight_chambre, types_chambre FROM villamar.chambres WHERE total_weight_chambre > 0 ORDER BY total_weight_chambre DESC LIMIT 10");
foreach ($chambresStock as $cs) {
    echo "Chambre {$cs->id} ({$cs->name_chambre} / {$cs->num_chambre}): {$cs->total_weight_chambre} KG\n";
    $types = json_decode($cs->types_chambre, true);
    if (!empty($types)) {
        echo "   Types count: " . count($types) . "\n";
        echo "   First item: " . json_encode($types[0]) . "\n";
    }
}
