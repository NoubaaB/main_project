<?php

$files = [
    'resources/js/locales/en/app.json',
    'resources/js/locales/fr/app.json',
    'resources/js/locales/es/app.json',
    'locals/en.json',
    'locals/fr.json',
    'locals/es.json',
];

$data = [];
foreach ($files as $f) {
    $path = __DIR__ . '/../' . $f;
    if (!file_exists($path)) {
        die("Missing file: $path\n");
    }
    $json = json_decode(file_get_contents($path), true);
    if ($json === null) {
        die("Invalid JSON in: $path\n");
    }
    $data[$f] = $json;
}

$allKeys = [];
foreach ($data as $f => $content) {
    $count = count($content);
    echo "$f: $count keys\n";
    $allKeys = array_merge($allKeys, array_keys($content));
}
$allKeys = array_unique($allKeys);
echo "\nTotal unique keys across all 6 files: " . count($allKeys) . "\n\n";

$hasDiscrepancy = false;
foreach ($data as $f => $content) {
    $diff = array_diff($allKeys, array_keys($content));
    if (count($diff) > 0) {
        $hasDiscrepancy = true;
        echo "File $f is missing " . count($diff) . " keys: " . implode(', ', array_slice($diff, 0, 5)) . "\n";
    }
}

if (!$hasDiscrepancy) {
    echo "SUCCESS: 100% key parity across all 6 locale files! Every file has exactly " . count($allKeys) . " keys.\n";
}

