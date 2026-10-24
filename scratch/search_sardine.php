<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$r = DB::select("SELECT id, name_type FROM villamar.types WHERE name_type LIKE '%SARDINE%' LIMIT 20");
echo json_encode($r, JSON_PRETTY_PRINT);
