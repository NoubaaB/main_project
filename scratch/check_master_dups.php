<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$checks = [
    ['tbl' => 'origins', 'col' => 'name_origin'],
    ['tbl' => 'places', 'col' => 'name_place'],
    ['tbl' => 'types', 'col' => 'name_type'],
    ['tbl' => 'containers', 'col' => 'name_container'],
];

foreach ($checks as $c) {
    $dups = DB::select("SELECT {$c['col']}, count(*) as cnt FROM villamar.{$c['tbl']} GROUP BY {$c['col']} HAVING cnt > 1");
    echo "Dups in {$c['tbl']}.{$c['col']}: " . count($dups) . "\n";
    foreach ($dups as $d) {
        $col = $c['col'];
        echo "  '{$d->$col}' appears {$d->cnt} times\n";
    }
}
