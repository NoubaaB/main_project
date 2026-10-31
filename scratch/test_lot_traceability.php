<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\WarehouseController;

$ctrl = new WarehouseController();
$req = new \Illuminate\Http\Request();
$res = $ctrl->lotTraceability($req, '3686');
$data = $res->getData();
echo "Lot: " . $data->lot_number . " Status: " . $data->status . "\n";
echo "Summary init weight: " . $data->summary->initial_weight . " Kg, Rem: " . $data->summary->remaining_weight . " Kg\n";
echo "Timeline events count: " . count($data->timeline) . "\n";
foreach ($data->timeline as $ev) {
    echo "  [{$ev->date}] {$ev->type} - {$ev->title} - {$ev->article} ({$ev->clean_type}): {$ev->weight} Kg\n";
}

