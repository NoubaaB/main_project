<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['detail_charges', 'detail_productions', 'detail_shippings'] as $tbl) {
    $cols = array_map(fn($c) => $c->Field, DB::select("DESCRIBE {$tbl}"));
    echo "=== $tbl ===\n" . implode(', ', $cols) . "\n\n";
}
