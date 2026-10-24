<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['types', 'containers', 'type_others'] as $tbl) {
    echo "=== villamar.$tbl ===\n";
    $cols = DB::select("DESCRIBE villamar.$tbl");
    foreach ($cols as $c) {
        echo "{$c->Field} ({$c->Type})\n";
    }
    $rows = DB::select("SELECT * FROM villamar.$tbl LIMIT 5");
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n\n";
}
