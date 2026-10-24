<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("DESCRIBE villamar.guano_usines");
echo "=== villamar.guano_usines columns ===\n";
foreach ($cols as $c) echo "{$c->Field} ({$c->Type})\n";

$rows = DB::select("SELECT * FROM villamar.guano_usines LIMIT 3");
echo "\n=== villamar.guano_usines sample ===\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
