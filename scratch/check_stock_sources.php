<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if congelations and congeles have overlapping IDs or bon numbers
$overlapBons = DB::select("
    SELECT c.num_bon_reception, count(*) as cnt 
    FROM villamar.congelations c
    INNER JOIN villamar.congeles cg ON cg.num_bon_reception = c.num_bon_reception
    WHERE c.num_bon_reception IS NOT NULL AND c.num_bon_reception != ''
    GROUP BY c.num_bon_reception
    LIMIT 5
");
echo "Overlap between congelations and congeles by num_bon_reception:\n";
print_r($overlapBons);

// Check stock_logs sum of qte_new - qte_old
$stockLogs = DB::select("SELECT SUM(qte_new - qte_old) as diff, SUM(qte_new) as total_new FROM villamar.stock_logs WHERE deleted_at IS NULL");
echo "\nstock_logs diff: " . number_format($stockLogs[0]->diff, 2) . " KG\n";
echo "stock_logs total_new: " . number_format($stockLogs[0]->total_new, 2) . " KG\n";

// Check types_chambre details in villamar.chambres
$chambres = DB::select("SELECT id, name_chambre, total_weight_chambre, types_chambre FROM villamar.chambres WHERE total_weight_chambre > 0");
$totalFromDetails = 0;
foreach ($chambres as $ch) {
    $types = json_decode($ch->types_chambre, true) ?: [];
    $chWeight = 0;
    foreach ($types as $t) {
        $chWeight += (float)($t['total_weight_container'] ?? 0);
        if (!empty($t['types_detail'])) {
            foreach ($t['types_detail'] as $td) {
                $totalFromDetails += (float)($td['total_weight_container'] ?? 0);
            }
        }
    }
}
echo "Sum from types_detail inside chambres.types_chambre: " . number_format($totalFromDetails, 2) . " KG\n";
