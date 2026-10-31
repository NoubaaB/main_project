<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "shipping_categories columns:\n";
print_r(Schema::getColumnListing('shipping_categories'));

echo "\nshipping_categories sample data:\n";
print_r(DB::table('shipping_categories')->limit(10)->get()->toArray());

echo "\nshippings columns:\n";
print_r(Schema::getColumnListing('shippings'));

echo "\nshippings sample category breakdown:\n";
print_r(DB::table('shippings')
    ->selectRaw('shipping_category_id, COUNT(*) as cnt, SUM(total_weight) as tw, SUM(total_price) as tp')
    ->groupBy('shipping_category_id')
    ->get()->toArray());

