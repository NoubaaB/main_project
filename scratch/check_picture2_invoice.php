<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$bills = DB::select("SELECT * FROM villamar.bills WHERE num_bill LIKE '%002600094%' OR num_bill LIKE '%2600094%'");
echo "=== villamar.bills for 002600094 ===\n";
echo json_encode($bills, JSON_PRETTY_PRINT) . "\n";
