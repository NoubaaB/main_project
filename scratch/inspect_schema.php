<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- SAMPLE POINTAGE DAYS ---\n";
$sample = DB::select("SELECT id, ouvrier_id, section_id, date, days FROM villamar.pointages WHERE days IS NOT NULL AND JSON_LENGTH(days) > 0 LIMIT 3");
foreach ($sample as $s) {
    echo "ID: {$s->id}, ouvrier: {$s->ouvrier_id}, section: " . ($s->section_id ?? 'null') . ", date: {$s->date}\n";
    $decoded = json_decode($s->days, true);
    echo "Days count: " . (is_array($decoded) ? count($decoded) : 0) . "\n";
    if (is_array($decoded) && count($decoded) > 0) {
        echo "First day: " . json_encode($decoded[0]) . "\n";
        echo "Last day: " . json_encode(end($decoded)) . "\n";
    }
    echo "\n";
}

echo "--- CORPORALS SCHEMA ---\n";
$cols = DB::select('DESCRIBE villamar.corporals');
foreach ($cols as $c) {
    echo $c->Field . ' (' . $c->Type . ")\n";
}

echo "\n--- OUVRIERS SCHEMA ---\n";
$cols = DB::select('DESCRIBE villamar.ouvriers');
foreach ($cols as $c) {
    echo $c->Field . ' (' . $c->Type . ")\n";
}
