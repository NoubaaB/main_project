<?php

$base = realpath(__DIR__ . '/../resources/js');
$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true) ?: [];
$fr = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/fr/app.json'), true) ?: [];
$es = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/es/app.json'), true) ?: [];

$folders = [
    'components/charges',
    'components/production',
    'components/shippings',
    'components/warehouses',
    'components/warehouse_transfers',
    'components/inventory',
    'components/workforce',
    'components/parameters',
    'components/dashboard',
    'components/jupiter_dashboard',
    'components/ClientInvoice',
    'components/ClientPayment',
    'components/SupplierInvoice',
    'components/SupplierPayment',
    'components/OrderTransfer',
    'components/admin',
    'components/schema',
    'components/ai_prompt',
    'components/shared',
    'components/common',
    'layouts',
    'router',
    'utils',
    'stores',
    'App.vue'
];

$allReferenced = [];
$missing = [];

foreach ($folders as $folder) {
    $fullPath = $base . '/' . $folder;
    if (!file_exists($fullPath)) continue;

    $files = is_file($fullPath) ? [$fullPath] : [];
    if (is_dir($fullPath)) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            if (in_array($file->getExtension(), ['vue', 'js'])) {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach ($files as $filePath) {
        $content = file_get_contents($filePath);
        $rel = str_replace($base, '', $filePath);

        // Match all $t('...') and this.$t('...')
        if (preg_match_all('/(?:\$t|this\.\$t|\bt|\$te|\$tc)\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]/m', $content, $m)) {
            foreach ($m[1] as $k) {
                $k = trim($k);
                if (strlen($k) < 2 || is_numeric($k)) continue;
                $allReferenced[$k] = true;

                if (!isset($en[$k]) || !isset($fr[$k]) || !isset($es[$k])) {
                    if (!isset($missing[$k])) $missing[$k] = [];
                    $missing[$k][] = $rel;
                }
            }
        }
    }
}

echo "Total checked referenced keys: " . count($allReferenced) . "\n";
echo "Missing keys found: " . count($missing) . "\n\n";

foreach ($missing as $k => $locs) {
    $uniqueLocs = array_unique($locs);
    $inEn = isset($en[$k]) ? 'YES' : 'NO';
    $inFr = isset($fr[$k]) ? 'YES' : 'NO';
    $inEs = isset($es[$k]) ? 'YES' : 'NO';
    echo "Key: '$k' (EN:$inEn, FR:$inFr, ES:$inEs)\n";
    echo "  Found in: " . implode(', ', array_slice($uniqueLocs, 0, 3)) . "\n";
}

