<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$dups = DB::select("SELECT name_provider, COUNT(*) as cnt FROM villamar.providers WHERE name_provider IS NOT NULL GROUP BY name_provider HAVING cnt > 1");
echo "Duplicate provider names: " . count($dups) . "\n";
foreach ($dups as $d) {
    echo "  '{$d->name_provider}' appears {$d->cnt} times\n";
}

$emptyNames = DB::select("SELECT COUNT(*) as cnt FROM villamar.providers WHERE name_provider IS NULL OR TRIM(name_provider) = ''")[0]->cnt;
echo "Empty provider names: {$emptyNames}\n";
