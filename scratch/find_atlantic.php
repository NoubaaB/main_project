<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$p = DB::select("SELECT * FROM villamar.providers WHERE name_provider LIKE '%Atlantic%' OR name_provider LIKE '%Kan%'");
echo "Providers matching Atlantic Kan:\n" . json_encode($p, JSON_PRETTY_PRINT) . "\n\n";

$b = DB::select("SELECT * FROM villamar.bills WHERE num_bill LIKE '%002600094%' OR num_bill LIKE '%2600094%' OR num_bill LIKE '%0094%'");
echo "Bills matching 002600094:\n" . json_encode($b, JSON_PRETTY_PRINT) . "\n\n";

$tbls = ['bills', 'bill_providers', 'congelation_clients', 'ventes', 'stock_exits', 'guano_usines'];
foreach ($tbls as $t) {
    $res = DB::select("SELECT count(*) as cnt FROM villamar.{$t} WHERE num_bill LIKE '%002600094%' OR num_bon_sortie LIKE '%ST26-964%' OR num_bon_reception LIKE '%ST26-964%'");
    echo "Table {$t} matches: " . $res[0]->cnt . "\n";
}
