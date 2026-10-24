<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['transports', 'glaces', 'rsws', 'others'];
foreach ($tables as $t) {
    $hasCols = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'villamar' AND TABLE_NAME = '{$t}' AND COLUMN_NAME IN ('is_facture', 'bill_id')");
    $colNames = array_column($hasCols, 'COLUMN_NAME');
    echo "Table {$t} has cols: " . implode(', ', $colNames) . "\n";
    if (in_array('is_facture', $colNames) && in_array('bill_id', $colNames)) {
        $cnt = DB::select("SELECT count(*) as cnt FROM villamar.{$t} WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0")[0]->cnt;
        echo "  Table {$t} with is_facture=1 and bill_id: {$cnt}\n";
    }
}
