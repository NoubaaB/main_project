<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WarehouseBalance;
use App\Models\Charge;
use App\Models\DetailCharge;
use App\Models\Production;
use App\Models\DetailProduction;
use App\Models\Shipping;
use App\Models\DetailShipping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TABLES AND COLUMNS FOR TRACKING NUMBERS ===\n";

// Check charges / detail_charges columns
$chargeCols = Schema::getColumnListing('charges');
$detailChargeCols = Schema::getColumnListing('detail_charges');
echo "charges columns containing 'track' or 'reception':\n";
foreach ($chargeCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false) echo "  $c\n";

echo "detail_charges columns containing 'track' or 'reception':\n";
foreach ($detailChargeCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false) echo "  $c\n";

// Check productions / detail_productions columns
$prodCols = Schema::getColumnListing('productions');
$detailProdCols = Schema::getColumnListing('detail_productions');
echo "productions columns:\n";
foreach ($prodCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false || stripos($c, 'lot') !== false) echo "  $c\n";

echo "detail_productions columns:\n";
foreach ($detailProdCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false || stripos($c, 'lot') !== false) echo "  $c\n";

// Check shippings / detail_shippings columns
$shipCols = Schema::getColumnListing('shippings');
$detailShipCols = Schema::getColumnListing('detail_shippings');
echo "shippings columns:\n";
foreach ($shipCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false || stripos($c, 'lot') !== false) echo "  $c\n";

echo "detail_shippings columns:\n";
foreach ($detailShipCols as $c) if (stripos($c, 'track') !== false || stripos($c, 'reception') !== false || stripos($c, 'num') !== false || stripos($c, 'lot') !== false) echo "  $c\n";

// Check warehouse_balances columns
$wbCols = Schema::getColumnListing('warehouse_balances');
echo "warehouse_balances relevant columns:\n";
foreach ($wbCols as $c) echo "  $c\n";

