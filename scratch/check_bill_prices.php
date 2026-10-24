<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bPos = DB::select("SELECT count(*) as cnt FROM villamar.bills WHERE total_price_sortie > 0")[0]->cnt;
$bZero = DB::select("SELECT count(*) as cnt FROM villamar.bills WHERE total_price_sortie = 0 OR total_price_sortie IS NULL")[0]->cnt;
echo "Bills price > 0: {$bPos}, price = 0: {$bZero}\n";

$bpPos = DB::select("SELECT count(*) as cnt FROM villamar.bill_providers WHERE total_price_sortie > 0")[0]->cnt;
$bpZero = DB::select("SELECT count(*) as cnt FROM villamar.bill_providers WHERE total_price_sortie = 0 OR total_price_sortie IS NULL")[0]->cnt;
echo "Bill providers price > 0: {$bpPos}, price = 0: {$bpZero}\n";
