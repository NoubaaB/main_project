<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("DESCRIBE villamar.stock_exits");
foreach ($cols as $c) echo "{$c->Field} ({$c->Type})\n";

$rows = DB::select("SELECT * FROM villamar.stock_exits LIMIT 2");
echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
