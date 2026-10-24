<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$charges = DB::table('charges')->where('invoice_id', 1477)->get();
echo "=== charges with invoice_id 1477 ===\n";
echo json_encode($charges, JSON_PRETTY_PRINT) . "\n";

foreach ($charges as $c) {
    $dc = DB::table('detail_charges')->where('charge_id', $c->id)->get();
    echo "=== detail_charges for charge {$c->id} ===\n";
    echo json_encode($dc, JSON_PRETTY_PRINT) . "\n";
}
