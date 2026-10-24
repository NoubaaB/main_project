<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$c = DB::select("SELECT * FROM villamar.congelations WHERE id IN (2076, 2115)");
echo json_encode($c, JSON_PRETTY_PRINT) . "\n";
