<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['fish_types', 'guano_types', 'package_types'] as $tbl) {
    $cols = array_map(fn($c) => $c->Field, DB::select("DESCRIBE {$tbl}"));
    echo "=== $tbl ===\n" . implode(', ', $cols) . "\n\n";
    $sample = DB::table($tbl)->limit(3)->get();
    echo json_encode($sample, JSON_PRETTY_PRINT) . "\n\n";
}
