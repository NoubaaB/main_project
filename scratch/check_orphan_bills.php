<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$allBP = DB::select("SELECT id, types_sortie FROM villamar.bill_providers");
$bpInFrais = DB::select("SELECT DISTINCT bill_id FROM villamar.frais WHERE is_facture = 1 AND bill_id IS NOT NULL");
$bpInCong = DB::select("SELECT DISTINCT bill_id FROM villamar.congelations WHERE is_facture = 1 AND bill_id IS NOT NULL");
$bpInCongeles = DB::select("SELECT DISTINCT bill_id FROM villamar.congeles WHERE is_facture = 1 AND bill_id IS NOT NULL");
$bpInAF = DB::select("SELECT DISTINCT bill_id FROM villamar.achat_frais WHERE is_facture = 1 AND bill_id IS NOT NULL");
$bpInGuanos = DB::select("SELECT DISTINCT bill_id FROM villamar.guanos WHERE is_facture = 1 AND bill_id IS NOT NULL");
$bpInEmb = DB::select("SELECT DISTINCT bill_id FROM villamar.emballages WHERE is_facture = 1 AND bill_id IS NOT NULL");

$set = array_flip(array_merge(
    array_column($bpInFrais, 'bill_id'),
    array_column($bpInCong, 'bill_id'),
    array_column($bpInCongeles, 'bill_id'),
    array_column($bpInAF, 'bill_id'),
    array_column($bpInGuanos, 'bill_id'),
    array_column($bpInEmb, 'bill_id')
));

$orphanBP = 0;
foreach ($allBP as $b) {
    if (!isset($set[$b->id])) {
        $orphanBP++;
    }
}

echo "Total bill_providers: " . count($allBP) . "\n";
echo "Orphan bill_providers: $orphanBP\n";
