<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== villamar.stocks ===\n";
print_r(DB::select("SELECT * FROM villamar.stocks"));

echo "\n=== villamar.types sample ===\n";
print_r(DB::select("SELECT id, name_type, code_article, process FROM villamar.types WHERE id IN (12, 32, 33, 40, 109)"));

echo "\n=== villamar.congelations (sample 2) ===\n";
print_r(DB::select("SELECT id, date_production, types_production, total_weight_production, is_facture, bill_id FROM villamar.congelations LIMIT 2"));

echo "\n=== villamar.congelation_clients (sample 2) ===\n";
print_r(DB::select("SELECT id, date_production, types_production, total_weight_production, is_facture, bill_id FROM villamar.congelation_clients LIMIT 2"));

echo "\n=== villamar.ventes (sample 2) ===\n";
print_r(DB::select("SELECT id, date_sortie, types_sortie, total_weight_sortie, is_facture, bill_id FROM villamar.ventes LIMIT 2"));

echo "\n=== villamar.stock_exits (sample 2) ===\n";
print_r(DB::select("SELECT * FROM villamar.stock_exits LIMIT 2"));
