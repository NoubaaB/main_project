<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$guanos = DB::table('guano_types')->get();
echo json_encode($guanos, JSON_PRETTY_PRINT);
