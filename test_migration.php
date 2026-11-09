<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$pdo->exec('DROP DATABASE IF EXISTS main_project_mig_test');
$pdo->exec('CREATE DATABASE main_project_mig_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "Database main_project_mig_test recreated.\n";

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.connections.mig_test' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'main_project_mig_test',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]]);

echo "Running migrations on main_project_mig_test...\n";
try {
    $exitCode = Illuminate\Support\Facades\Artisan::call('migrate', [
        '--database' => 'mig_test',
        '--force' => true,
    ]);
    echo "Artisan migrate exit code: $exitCode\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
