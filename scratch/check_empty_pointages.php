<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$emptyDays = DB::select("SELECT COUNT(*) as cnt, SUM(total) as sum_total, SUM(total_1) as sum_t1, SUM(total_2) as sum_t2 FROM villamar.pointages WHERE days IS NULL OR JSON_LENGTH(days) = 0");
echo "Empty days records count: " . $emptyDays[0]->cnt . "\n";
echo "Sum total: " . ($emptyDays[0]->sum_total ?? 0) . "\n";
echo "Sum total_1: " . ($emptyDays[0]->sum_t1 ?? 0) . "\n";
echo "Sum total_2: " . ($emptyDays[0]->sum_t2 ?? 0) . "\n";
