<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$dups = DB::select('SELECT matricule, COUNT(*) as cnt FROM villamar.ouvriers WHERE matricule IS NOT NULL GROUP BY matricule HAVING cnt > 1');
echo "Duplicate matricules in ouvriers: " . count($dups) . "\n";
foreach (array_slice($dups, 0, 5) as $d) {
    echo "Matricule {$d->matricule} appears {$d->cnt} times\n";
}

$nullMatricules = DB::select('SELECT COUNT(*) as cnt FROM villamar.ouvriers WHERE matricule IS NULL OR matricule = 0')[0]->cnt;
echo "Null/zero matricules: {$nullMatricules}\n";

$ouvrierCount = DB::select('SELECT COUNT(*) as cnt FROM villamar.ouvriers')[0]->cnt;
echo "Total ouvriers: {$ouvrierCount}\n";
