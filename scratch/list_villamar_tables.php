<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = DB::select('SHOW TABLES FROM villamar');
foreach ($tables as $t) {
    $arr = (array) $t;
    $tbl = array_values($arr)[0];
    $count = DB::select("SELECT count(*) as c FROM villamar.`$tbl`")[0]->c;
    echo sprintf("%-30s : %d rows\n", $tbl, $count);
}
