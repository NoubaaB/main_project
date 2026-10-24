<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'villamar' ORDER BY TABLE_NAME");
foreach ($tables as $t) {
    echo str_pad($t->TABLE_NAME, 30) . " : " . $t->TABLE_ROWS . " rows\n";
}
