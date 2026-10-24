<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$types = DB::select("SELECT id, name_type, code_article FROM villamar.types WHERE id IN (119, 148)");
echo json_encode($types, JSON_PRETTY_PRINT) . "\n";
