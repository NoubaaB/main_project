<?php

$locales = ['en', 'fr', 'es'];
$translations = [];

foreach ($locales as $l) {
    $path = __DIR__ . "/../locals/{$l}.json";
    $translations[$l] = json_decode(file_get_contents($path), true) ?: [];
}

$allKeys = array_unique(array_merge(
    array_keys($translations['en']),
    array_keys($translations['fr']),
    array_keys($translations['es'])
));

sort($allKeys);
echo "Total unique keys in translation files: " . count($allKeys) . "\n";
echo "Keys in EN: " . count($translations['en']) . "\n";
echo "Keys in FR: " . count($translations['fr']) . "\n";
echo "Keys in ES: " . count($translations['es']) . "\n\n";

$missingInEn = [];
$missingInFr = [];
$missingInEs = [];

foreach ($allKeys as $k) {
    if (!isset($translations['en'][$k]) || $translations['en'][$k] === '') {
        $missingInEn[] = $k;
    }
    if (!isset($translations['fr'][$k]) || $translations['fr'][$k] === '') {
        $missingInFr[] = $k;
    }
    if (!isset($translations['es'][$k]) || $translations['es'][$k] === '') {
        $missingInEs[] = $k;
    }
}

echo "Keys existing in other languages but missing in EN: " . count($missingInEn) . "\n";
if (!empty($missingInEn)) {
    echo "  " . implode(', ', array_slice($missingInEn, 0, 30)) . "\n";
}

echo "Keys existing in other languages but missing in FR: " . count($missingInFr) . "\n";
if (!empty($missingInFr)) {
    echo "  " . implode(', ', array_slice($missingInFr, 0, 30)) . "\n";
}

echo "Keys existing in other languages but missing in ES: " . count($missingInEs) . "\n";
if (!empty($missingInEs)) {
    echo "  " . implode(', ', array_slice($missingInEs, 0, 30)) . "\n";
}

