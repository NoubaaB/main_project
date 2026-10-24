<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("DESCRIBE villamar.emballages");
echo "=== villamar.emballages columns ===\n";
foreach ($cols as $c) echo "{$c->Field} ({$c->Type})\n";

$rows = DB::select("SELECT * FROM villamar.emballages LIMIT 5");
echo "\n=== villamar.emballages sample ===\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
