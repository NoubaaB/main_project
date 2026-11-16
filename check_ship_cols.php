<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "shippings: " . implode(', ', Schema::getColumnListing('shippings')) . "\n";
echo "detail_shippings: " . implode(', ', Schema::getColumnListing('detail_shippings')) . "\n";

