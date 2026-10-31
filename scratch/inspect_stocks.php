<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== villamar.stocks ===\n";
print_r(DB::select("SELECT * FROM villamar.stocks LIMIT 5"));

echo "\n=== villamar.stock_logs ===\n";
print_r(DB::select("SELECT * FROM villamar.stock_logs LIMIT 5"));

echo "\n=== villamar.stock_exits ===\n";
print_r(DB::select("SELECT * FROM villamar.stock_exits LIMIT 5"));

echo "\n=== villamar.history_stocks ===\n";
print_r(DB::select("SELECT * FROM villamar.history_stocks LIMIT 5"));

echo "\n=== villamar.stock_movement_logs ===\n";
print_r(DB::select("SELECT * FROM villamar.stock_movement_logs LIMIT 5"));
