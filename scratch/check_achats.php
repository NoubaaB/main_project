<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- ACHATS SCHEMA ---\n";
foreach (DB::select('DESCRIBE villamar.achats') as $c) {
    echo $c->Field . ' (' . $c->Type . ")\n";
}

$sample = DB::select("SELECT * FROM villamar.achats LIMIT 3");
echo "Sample achats:\n" . json_encode($sample, JSON_PRETTY_PRINT) . "\n";

$cntBilled = DB::select("SELECT count(*) as cnt FROM villamar.achats WHERE is_facture = 1")[0]->cnt ?? 0;
echo "Achats is_facture=1 count: {$cntBilled}\n";
