<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$emptyTypesSortie = DB::select("SELECT count(*) as cnt FROM villamar.ventes WHERE types_sortie IS NULL OR types_sortie = '' OR types_sortie = '[]'")[0]->cnt;
$nonEmptyTypesSortie = DB::select("SELECT count(*) as cnt FROM villamar.ventes WHERE types_sortie IS NOT NULL AND types_sortie != '' AND types_sortie != '[]'")[0]->cnt;

echo "Ventes with empty types_sortie: {$emptyTypesSortie}, non-empty: {$nonEmptyTypesSortie}\n";

$emptyInvoiced = DB::select("SELECT count(*) as cnt FROM villamar.ventes WHERE is_facture = 1 AND (types_sortie IS NULL OR types_sortie = '' OR types_sortie = '[]')")[0]->cnt;
echo "Invoiced ventes with empty types_sortie: {$emptyInvoiced}\n";
