<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$c1 = DB::select("SELECT * FROM charges WHERE id in (8879, 8918)");
echo "Charges 8879, 8918:\n" . json_encode($c1, JSON_PRETTY_PRINT) . "\n\n";

$dc = DB::select("SELECT * FROM detail_charges WHERE charge_id in (8879, 8918)");
echo "Detail charges for 8879, 8918:\n" . json_encode($dc, JSON_PRETTY_PRINT) . "\n\n";
