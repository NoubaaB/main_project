<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$trucks = DB::table('trucks')->whereIn('registration_plate', ['5786-A-68', '88765-A-33', '32209-A-33', '66948-A-34', '3490-A-70'])->get();
echo "=== matched trucks ===\n" . json_encode($trucks, JSON_PRETTY_PRINT) . "\n";
