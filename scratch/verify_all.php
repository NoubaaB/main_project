<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\SupplierInvoice;
use App\Models\ClientInvoice;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=====================================================\n";
echo "           FULL POST-MIGRATION AUDIT                 \n";
echo "=====================================================\n\n";

// 1. Verify Picture 1 Supplier Invoice (ID 1477, num_bill 00260905.1)
$si1477 = SupplierInvoice::with([
    'charges.trucks',
    'charges.detailCharges.stockable',
    'supplier'
])->find(1477);

if ($si1477) {
    echo "1. Supplier Invoice 1477 found:\n";
    echo "   Date: {$si1477->date}\n";
    echo "   Supplier: {$si1477->supplier->name}\n";
    echo "   Tracking: " . json_encode($si1477->tracking_numbers) . "\n";
    echo "   Total Weight: {$si1477->total_weight}\n";
    echo "   Total Price: {$si1477->total_price}\n";
    echo "   Charges count: " . count($si1477->charges) . "\n";
    
    $rowIdx = 0;
    foreach ($si1477->charges as $c) {
        $truckPlates = $c->trucks->pluck('registration_plate')->implode(', ');
        $rtn = json_encode($c->reception_tracking_number);
        foreach ($c->detailCharges as $dc) {
            $rowIdx++;
            echo "   Row $rowIdx: Date: {$c->date} | Designation: '{$dc->article_description}' | Stockable: '{$dc->stockable_type}' #{$dc->stockable_id} | Trucks: '{$truckPlates}' | RTN: {$rtn} | Weight: {$dc->weight} | PU: {$dc->unit_price} | Price: {$dc->total_price}\n";
        }
    }
} else {
    echo "ERROR: Supplier Invoice 1477 NOT found!\n";
}

echo "\n";

// 2. Check null article_descriptions across tables
$nullDc = DB::table('detail_charges')->whereNull('article_description')->orWhere('article_description', '')->count();
$nullDp = DB::table('detail_productions')->whereNull('article_description')->orWhere('article_description', '')->count();
$nullDs = DB::table('detail_shippings')->whereNull('article_description')->orWhere('article_description', '')->count();

echo "2. Null/Empty article_description count:\n";
echo "   detail_charges: {$nullDc}\n";
echo "   detail_productions: {$nullDp}\n";
echo "   detail_shippings: {$nullDs}\n";

echo "\n";

// 3. Sample client invoice with congelation_clients
$ciSub = ClientInvoice::with([
    'productions.detailProductions',
    'client'
])->whereHas('productions', fn($q) => $q->where('production_category_id', 2))->first();

if ($ciSub) {
    echo "3. Sample Client Invoice with Sous-Traitance (ID {$ciSub->id}):\n";
    echo "   Client: {$ciSub->client->name}\n";
    echo "   Total Price: {$ciSub->total_price}\n";
    foreach ($ciSub->productions as $p) {
        foreach ($p->detailProductions as $dp) {
            echo "   Prod Detail: Designation: '{$dp->article_description}' | Weight: {$dp->weight} | PU: {$dp->unit_price} | Price: {$dp->total_price}\n";
        }
    }
}

echo "\n";

// 4. Sample client invoice with sales (ventes)
$ciSale = ClientInvoice::with([
    'shippings.trucks',
    'shippings.detailShippings',
    'client'
])->whereHas('shippings', fn($q) => $q->where('shipping_category_id', 1))->first();

if ($ciSale) {
    echo "4. Sample Client Invoice with Sales (ID {$ciSale->id}):\n";
    echo "   Client: {$ciSale->client->name}\n";
    echo "   Total Price: {$ciSale->total_price}\n";
    foreach ($ciSale->shippings as $s) {
        $truckPlates = $s->trucks->pluck('registration_plate')->implode(', ');
        foreach ($s->detailShippings as $ds) {
            echo "   Shipping Detail: Designation: '{$ds->article_description}' | Trucks: '{$truckPlates}' | Weight: {$ds->weight} | PU: {$ds->unit_price} | Price: {$ds->total_price}\n";
        }
    }
}

echo "\n";

// 5. Total counts & settlements
$siCount = DB::table('supplier_invoices')->count();
$spCount = DB::table('supplier_payments')->count();
$ciCount = DB::table('client_invoices')->count();
$cpCount = DB::table('client_payments')->count();
$faCount = DB::table('fortnight_activities')->count();
$faSum = DB::table('fortnight_activities')->sum('price');
$chargeTruckCount = DB::table('charge_truck')->count();
$shippingTruckCount = DB::table('shipping_truck')->count();

echo "5. Total Counts:\n";
echo "   Supplier Invoices: {$siCount} | Supplier Settlements: {$spCount}\n";
echo "   Client Invoices: {$ciCount} | Client Settlements: {$cpCount}\n";
echo "   Fortnight Activities: {$faCount} (Total Price: " . number_format($faSum, 2) . " MAD)\n";
echo "   charge_truck links: {$chargeTruckCount}\n";
echo "   shipping_truck links: {$shippingTruckCount}\n";

echo "\n";

// 6. Test Admin User
$admin = User::where('email', 'test@test.com')->first();
$passwordCheck = $admin && Hash::check('password', $admin->password);
echo "6. Admin User 'test@test.com': " . ($passwordCheck ? "VALID PASSWORD" : "FAIL") . "\n";

echo "\n=====================================================\n";
