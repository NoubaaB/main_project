<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$guanoVentes = DB::select("SELECT id, type, type_vente, types_sortie FROM villamar.ventes WHERE type = 'guano' LIMIT 3");
echo "=== guano ventes ===\n" . json_encode($guanoVentes, JSON_PRETTY_PRINT) . "\n";

$fishVentes = DB::select("SELECT id, type, type_vente, types_sortie FROM villamar.ventes WHERE type != 'guano' AND types_sortie IS NOT NULL LIMIT 3");
echo "=== fish ventes ===\n" . json_encode($fishVentes, JSON_PRETTY_PRINT) . "\n";
