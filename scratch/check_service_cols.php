<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['rsws', 'glaces', 'transports', 'maintenances', 'others'] as $tbl) {
    echo "=== villamar.$tbl ===\n";
    $cols = DB::select("DESCRIBE villamar.$tbl");
    foreach ($cols as $c) echo "{$c->Field} ({$c->Type})\n";
}
