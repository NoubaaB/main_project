<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$enPath = __DIR__ . '/../resources/js/locales/en/app.json';
$en = json_decode(file_get_contents($enPath), true) ?: [];

$groups = App\Models\Role::permissionGroups();
$missingPermGroups = [];
$missingPerms = [];

foreach ($groups as $group => $perms) {
    $groupKey = 'perm_group_' . $group;
    if (!isset($en[$groupKey])) {
        $missingPermGroups[] = $groupKey;
    }
    foreach ($perms as $perm) {
        $permKey = 'perm_' . str_replace('.', '_', $perm);
        if (!isset($en[$permKey])) {
            $missingPerms[] = $permKey;
        }
    }
}

echo "Missing perm_group keys: " . count($missingPermGroups) . "\n";
foreach ($missingPermGroups as $g) {
    echo "  - $g\n";
}

echo "\nMissing perm keys: " . count($missingPerms) . "\n";
foreach ($missingPerms as $p) {
    echo "  - $p\n";
}

