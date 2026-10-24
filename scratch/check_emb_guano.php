<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- EMBALLAGES COLS ---\n";
foreach (DB::select("DESCRIBE villamar.emballages") as $c) echo $c->Field . "\n";

$embSample = DB::select("SELECT * FROM villamar.emballages LIMIT 2");
echo "Sample emballages:\n" . json_encode($embSample, JSON_PRETTY_PRINT) . "\n";

echo "--- GUANOS COLS ---\n";
foreach (DB::select("DESCRIBE villamar.guanos") as $c) echo $c->Field . "\n";

$guSample = DB::select("SELECT * FROM villamar.guanos LIMIT 2");
echo "Sample guanos:\n" . json_encode($guSample, JSON_PRETTY_PRINT) . "\n";
