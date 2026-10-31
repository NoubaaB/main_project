<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$sml = DB::select("SELECT * FROM villamar.stock_movement_logs WHERE deleted_at IS NULL LIMIT 5");
echo "=== Sample 5 active stock_movement_logs ===\n";
print_r($sml);

$activeCount = DB::select("SELECT count(*) as cnt FROM villamar.stock_movement_logs WHERE deleted_at IS NULL")[0]->cnt;
echo "Active stock_movement_logs: $activeCount\n";
