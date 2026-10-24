<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::select("SELECT id, name_fr, name_in_invoice FROM fish_types WHERE name_fr LIKE '%SARDINE%' LIMIT 10");
echo "Fish types with SARDINE:\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
