<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$indexes = Illuminate\Support\Facades\DB::select('SHOW INDEX FROM warehouse_balances');
foreach ($indexes as $idx) {
    echo $idx->Key_name . ' -> ' . $idx->Column_name . ' (' . $idx->Seq_in_index . ")\n";
}
echo "\n--- warehouse_balance_operations ---\n";
$indexes2 = Illuminate\Support\Facades\DB::select('SHOW INDEX FROM warehouse_balance_operations');
foreach ($indexes2 as $idx) {
    echo $idx->Key_name . ' -> ' . $idx->Column_name . ' (' . $idx->Seq_in_index . ")\n";
}
