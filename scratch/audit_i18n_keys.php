<?php

$vueDir = __DIR__ . '/../resources/js';
$enPath = __DIR__ . '/../resources/js/locales/en/app.json';
$frPath = __DIR__ . '/../resources/js/locales/fr/app.json';
$esPath = __DIR__ . '/../resources/js/locales/es/app.json';

$en = json_decode(file_get_contents($enPath), true) ?: [];
$fr = json_decode(file_get_contents($frPath), true) ?: [];
$es = json_decode(file_get_contents($esPath), true) ?: [];

echo "Existing keys count:\n";
echo "  EN: " . count($en) . "\n";
echo "  FR: " . count($fr) . "\n";
echo "  ES: " . count($es) . "\n\n";

// 1. Cross-parity check among en, fr, es
$allKeys = array_unique(array_merge(array_keys($en), array_keys($fr), array_keys($es)));
echo "Total unique keys in json files: " . count($allKeys) . "\n";

$missingInEn = array_diff($allKeys, array_keys($en));
$missingInFr = array_diff($allKeys, array_keys($fr));
$missingInEs = array_diff($allKeys, array_keys($es));

echo "Cross-file parity gaps:\n";
echo "  Missing in EN: " . count($missingInEn) . "\n";
echo "  Missing in FR: " . count($missingInFr) . "\n";
echo "  Missing in ES: " . count($missingInEs) . "\n\n";

// 2. Scan Vue and JS files for translation key references
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
$referencedKeys = [];
$keyLocations = [];

$patterns = [
    '/\$t\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
    '/this\.\$t\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
    '/\bt\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
    '/\$te\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
    '/\$tc\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
    '/i18n\.t\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]\s*[\),]/',
];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['vue', 'js', 'ts'])) continue;

    $content = file_get_contents($file->getPathname());
    $relPath = str_replace(realpath($vueDir), '', realpath($file->getPathname()));

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $k) {
                // Filter out non-keys like variables or paths or format strings
                if (strlen($k) > 1 && !is_numeric($k) && !str_starts_with($k, 'http')) {
                    $referencedKeys[$k] = true;
                    if (!isset($keyLocations[$k])) {
                        $keyLocations[$k] = [];
                    }
                    if (count($keyLocations[$k]) < 3) {
                        $keyLocations[$k][] = $relPath;
                    }
                }
            }
        }
    }
}

echo "Total unique translation keys referenced in codebase: " . count($referencedKeys) . "\n";

$referencedKeysList = array_keys($referencedKeys);
$missingFromReferenced = array_diff($referencedKeysList, $allKeys);

echo "Referenced keys MISSING from locale json files: " . count($missingFromReferenced) . "\n\n";

if (count($missingFromReferenced) > 0) {
    echo "Top 100 missing referenced keys:\n";
    $i = 0;
    foreach ($missingFromReferenced as $k) {
        $locs = implode(', ', $keyLocations[$k] ?? []);
        echo "  - '$k' (found in: $locs)\n";
        if (++$i >= 100) break;
    }
}

