<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== history_stocks columns ===\n";
foreach (DB::select("DESCRIBE villamar.history_stocks") as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== history_stocks sample 3 ===\n";
print_r(DB::select("SELECT * FROM villamar.history_stocks LIMIT 3"));

echo "\n=== history_stocks latest 3 ===\n";
print_r(DB::select("SELECT * FROM villamar.history_stocks ORDER BY id DESC LIMIT 3"));
