<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$ds = DB::select("
    SELECT s.id, s.date, s.invoice_id, ds.id as ds_id, ds.stockable_type, ds.stockable_id, ds.article_description, ds.weight, ds.unit_price, ds.total_price 
    FROM detail_shippings ds
    JOIN shippings s ON s.id = ds.shipping_id
    WHERE s.shipping_tracking_number LIKE '%2251%' OR s.id = 10345
");
echo "Shippings matching 2251 / 10345 in Jupiter:\n" . json_encode($ds, JSON_PRETTY_PRINT) . "\n";
