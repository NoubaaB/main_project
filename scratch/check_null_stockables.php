<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$nullDc = DB::select("
    SELECT c.id, c.charge_category_id, dc.id as dc_id, dc.weight, dc.total_price 
    FROM detail_charges dc
    JOIN charges c ON c.id = dc.charge_id
    WHERE dc.stockable_id IS NULL 
    LIMIT 10
");
echo "Null stockable_id in detail_charges sample:\n" . json_encode($nullDc, JSON_PRETTY_PRINT) . "\n";

$catBreakdown = DB::select("
    SELECT c.charge_category_id, count(*) as cnt
    FROM detail_charges dc
    JOIN charges c ON c.id = dc.charge_id
    WHERE dc.stockable_id IS NULL
    GROUP BY c.charge_category_id
");
echo "Null stockable_id by charge category:\n" . json_encode($catBreakdown, JSON_PRETTY_PRINT) . "\n";
