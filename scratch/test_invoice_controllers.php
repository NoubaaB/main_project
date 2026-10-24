<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\ClientInvoiceController;

echo "=== Testing SupplierInvoiceController::supplierInvoicesByIds(1477) ===\n";
$reqSupplier = Request::create('/api/supplier_invoice/ids', 'GET', ['ids' => '1477']);
$ctrlSupplier = new SupplierInvoiceController();
$resSupplier = $ctrlSupplier->supplierInvoicesByIds($reqSupplier);
$dataSupplier = json_decode($resSupplier->getContent(), true);

$inv1477 = $dataSupplier['supplier_invoices'][0] ?? null;
if ($inv1477) {
    echo "Invoice ID: {$inv1477['id']}\n";
    echo "Supplier: " . ($inv1477['supplier']['name'] ?? 'N/A') . "\n";
    echo "Date: {$inv1477['date']}\n";
    echo "Charges count: " . count($inv1477['charges'] ?? []) . "\n";
    foreach ($inv1477['charges'] as $c) {
        $truckPlates = array_map(fn($t) => $t['registration_plate'], $c['trucks'] ?? []);
        echo "  Charge ID {$c['id']} | Trucks: " . implode(', ', $truckPlates) . " | RTN: " . json_encode($c['reception_tracking_number']) . "\n";
        foreach ($c['detail_charges'] as $dc) {
            echo "    -> Detail ID {$dc['id']} | Designation (article_description): '{$dc['article_description']}' | Stockable: " . ($dc['stockable']['name_in_invoice'] ?? 'N/A') . " | Weight: {$dc['weight']} | PU: {$dc['unit_price']} | Total: {$dc['total_price']}\n";
        }
    }
} else {
    echo "Supplier invoice 1477 not returned by controller!\n";
}

echo "\n=== Testing ClientInvoiceController::clientInvoicesByIds(601) ===\n";
$reqClient = Request::create('/api/client_invoice/ids', 'GET', ['ids' => '601']);
$ctrlClient = new ClientInvoiceController();
$resClient = $ctrlClient->clientInvoicesByIds($reqClient);
$dataClient = json_decode($resClient->getContent(), true);

$inv601 = $dataClient['client_invoices'][0] ?? null;
if ($inv601) {
    echo "Invoice ID: {$inv601['id']}\n";
    echo "Client: " . ($inv601['client']['name'] ?? 'N/A') . "\n";
    echo "Total Price: {$inv601['total_price']}\n";
    echo "Productions count: " . count($inv601['productions'] ?? []) . "\n";
    foreach ($inv601['productions'] as $p) {
        foreach ($p['detail_productions'] as $dp) {
            echo "    -> Detail ID {$dp['id']} | Designation: '{$dp['article_description']}' | Stockable: " . ($dp['stockable']['name_in_invoice'] ?? 'N/A') . " | Weight: {$dp['weight']} | PU: {$dp['unit_price']} | Total: {$dp['total_price']}\n";
        }
    }
} else {
    echo "Client invoice 601 not returned by controller!\n";
}
