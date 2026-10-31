<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    DB::statement("CREATE INDEX idx_wbo_date_del ON warehouse_balance_operations (date, deleted_at)");
    echo "Created idx_wbo_date_del successfully.\n";
} catch (\Exception $e) {
    echo "idx_wbo_date_del error/already exists: " . $e->getMessage() . "\n";
}

try {
    DB::statement("CREATE INDEX idx_wb_date_del_op ON warehouse_balances (date, deleted_at, warehouse_operation)");
    echo "Created idx_wb_date_del_op successfully.\n";
} catch (\Exception $e) {
    echo "idx_wb_date_del_op error/already exists: " . $e->getMessage() . "\n";
}

