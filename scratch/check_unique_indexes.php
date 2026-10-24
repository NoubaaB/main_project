<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['origins', 'fisheries', 'fish_types', 'package_types', 'ships', 'trucks', 'clients', 'suppliers', 'warehouses', 'sections', 'corporals', 'workforces'];

foreach ($tables as $t) {
    $indexes = DB::select("SHOW INDEX FROM main_project.{$t} WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
    echo "Table {$t} unique indexes:\n";
    foreach ($indexes as $idx) {
        echo "  - {$idx->Key_name} on {$idx->Column_name}\n";
    }
}
