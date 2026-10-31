<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== congeles count ===\n";
echo DB::select("SELECT count(*) as cnt FROM villamar.congeles")[0]->cnt . "\n";
echo "Sample congeles:\n";
print_r(DB::select("SELECT * FROM villamar.congeles LIMIT 2"));

echo "=== congelations count ===\n";
echo DB::select("SELECT count(*) as cnt FROM villamar.congelations")[0]->cnt . "\n";

echo "=== achats count ===\n";
echo DB::select("SELECT count(*) as cnt FROM villamar.achats")[0]->cnt . "\n";
echo "Sample achats:\n";
print_r(DB::select("SELECT * FROM villamar.achats LIMIT 2"));
