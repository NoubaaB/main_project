<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ClientInvoiceController;

$reqClient = Request::create('/api/client_invoice/ids', 'GET', ['ids' => '7']);
$ctrlClient = new ClientInvoiceController();
$resClient = $ctrlClient->clientInvoicesByIds($reqClient);
$dataClient = json_decode($resClient->getContent(), true);

$inv7 = $dataClient['client_invoices'][0] ?? null;
if ($inv7) {
    echo "Invoice ID: {$inv7['id']}\n";
    echo "Client: " . ($inv7['client']['name'] ?? 'N/A') . "\n";
    echo "Total Price: {$inv7['total_price']}\n";
    echo "Shippings count: " . count($inv7['shippings'] ?? []) . "\n";
    foreach ($inv7['shippings'] as $s) {
        $truckPlates = array_map(fn($t) => $t['registration_plate'], $s['trucks'] ?? []);
        echo "  Shipping ID {$s['id']} | Trucks: " . implode(', ', $truckPlates) . " | Tracking: " . json_encode($s['shipping_tracking_number']) . "\n";
        foreach ($s['detail_shippings'] as $ds) {
            echo "    -> Detail ID {$ds['id']} | Designation: '{$ds['article_description']}' | Stockable: " . ($ds['stockable']['name_in_invoice'] ?? 'N/A') . " | Weight: {$ds['weight']} | PU: {$ds['unit_price']} | Total: {$ds['total_price']}\n";
        }
    }
}
