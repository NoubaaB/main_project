<?php

$vueDir = __DIR__ . '/../resources/js';
$enPath = __DIR__ . '/../resources/js/locales/en/app.json';
$en = json_decode(file_get_contents($enPath), true) ?: [];

// 1. Check router/routes.js and routeMetadata.js
$routesPath = __DIR__ . '/../resources/js/router/routes.js';
$metaPath = __DIR__ . '/../resources/js/utils/routeMetadata.js';

$routeKeys = [];
foreach ([$routesPath, $metaPath] as $rp) {
    if (file_exists($rp)) {
        $content = file_get_contents($rp);
        if (preg_match_all('/title:\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/', $content, $m)) {
            foreach ($m[1] as $k) {
                if (!isset($en[$k])) {
                    $routeKeys[] = $k;
                }
            }
        }
    }
}

echo "Missing route title keys: " . count($routeKeys) . "\n";
foreach ($routeKeys as $rk) {
    echo "  - Route key: $rk\n";
}

// 2. Check table headers in all script.js and index.vue
$missingTableHeaders = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['vue', 'js'])) continue;

    $content = file_get_contents($file->getPathname());
    // Match headers arrays: { title: 'something', ... } or title: this.$t('something')
    if (preg_match_all('/title:\s*(?:this\.\$t\(\s*|t\(\s*|\$t\(\s*)?[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\)?/', $content, $m)) {
        foreach ($m[1] as $headerKey) {
            if (!isset($en[$headerKey]) && strlen($headerKey) > 2 && !is_numeric($headerKey)) {
                $missingTableHeaders[$headerKey] = $file->getFilename();
            }
        }
    }
}

echo "\nMissing table header/title keys: " . count($missingTableHeaders) . "\n";
foreach ($missingTableHeaders as $thk => $fn) {
    echo "  - $thk ($fn)\n";
}

// 3. Check for empty string values in existing locale files
$frPath = __DIR__ . '/../resources/js/locales/fr/app.json';
$esPath = __DIR__ . '/../resources/js/locales/es/app.json';
$fr = json_decode(file_get_contents($frPath), true) ?: [];
$es = json_decode(file_get_contents($esPath), true) ?: [];

$emptyEn = [];
$emptyFr = [];
$emptyEs = [];
foreach ($en as $k => $v) {
    if (trim($v) === '') $emptyEn[] = $k;
}
foreach ($fr as $k => $v) {
    if (trim($v) === '') $emptyFr[] = $k;
}
foreach ($es as $k => $v) {
    if (trim($v) === '') $emptyEs[] = $k;
}

echo "\nEmpty translation values in JSON:\n";
echo "  EN empty: " . count($emptyEn) . "\n";
echo "  FR empty: " . count($emptyFr) . "\n";
echo "  ES empty: " . count($emptyEs) . "\n";

if (count($emptyEn) > 0) {
    echo "Sample EN empty keys: " . implode(', ', array_slice($emptyEn, 0, 10)) . "\n";
}
if (count($emptyFr) > 0) {
    echo "Sample FR empty keys: " . implode(', ', array_slice($emptyFr, 0, 10)) . "\n";
}
if (count($emptyEs) > 0) {
    echo "Sample ES empty keys: " . implode(', ', array_slice($emptyEs, 0, 10)) . "\n";
}


