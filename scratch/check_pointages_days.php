<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$totalPointages = DB::select("SELECT COUNT(*) as cnt FROM villamar.pointages")[0]->cnt;
echo "Total pointages: {$totalPointages}\n";

$rows = DB::select("SELECT id, ouvrier_id, section_id, date, days, total, total_1, total_2 FROM villamar.pointages WHERE days IS NOT NULL AND JSON_LENGTH(days) > 0");
echo "Pointages with non-empty days: " . count($rows) . "\n";

$totalDays = 0;
$totalMontant = 0;
$zeroMontantDays = 0;
$validDates = 0;
$invalidDates = 0;

foreach ($rows as $r) {
    $days = json_decode($r->days, true);
    if (!is_array($days)) continue;
    foreach ($days as $d) {
        $totalDays++;
        $montant = isset($d['montant']) ? (float)$d['montant'] : 0;
        $totalMontant += $montant;
        if ($montant <= 0) $zeroMontantDays++;
        
        $dateStr = $d['start'] ?? null;
        if ($dateStr && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            $validDates++;
        } else {
            $invalidDates++;
        }
    }
}

echo "Total daily activity entries in days JSON: {$totalDays}\n";
echo "Total montant: " . number_format($totalMontant, 2) . " MAD\n";
echo "Zero montant days: {$zeroMontantDays}\n";
echo "Valid YYYY-MM-DD dates: {$validDates}, Invalid dates: {$invalidDates}\n";
