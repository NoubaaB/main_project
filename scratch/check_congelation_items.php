<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$cRows = DB::select("SELECT id, types_production FROM villamar.congelations LIMIT 5");
echo "=== congelations types_production sample ===\n";
foreach ($cRows as $r) {
    echo "ID {$r->id}: " . substr($r->types_production, 0, 150) . "...\n";
}

$cgRows = DB::select("SELECT id, types_production FROM villamar.congeles LIMIT 5");
echo "\n=== congeles types_production sample ===\n";
foreach ($cgRows as $r) {
    echo "ID {$r->id}: " . substr($r->types_production, 0, 150) . "...\n";
}
