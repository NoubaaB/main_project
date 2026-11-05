<?php

$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true);
$fr = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/fr/app.json'), true);
$es = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/es/app.json'), true);

$files = [
    __DIR__ . '/../resources/js/components/ai_prompt/index.vue',
    __DIR__ . '/../resources/js/components/ai_prompt/AiMessageBox.vue',
];

$keys = [];
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (preg_match_all("/\\\$t\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m)) {
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }
    }
}

echo "Found " . count($keys) . " translation keys in ai_prompt components.\n";

$missingEn = [];
$missingFr = [];
$missingEs = [];

foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) $missingEn[] = $k;
    if (!isset($fr[$k])) $missingFr[] = $k;
    if (!isset($es[$k])) $missingEs[] = $k;
}

echo "Missing in EN (" . count($missingEn) . "): " . implode(', ', $missingEn) . "\n";
echo "Missing in FR (" . count($missingFr) . "): " . implode(', ', $missingFr) . "\n";
echo "Missing in ES (" . count($missingEs) . "): " . implode(', ', $missingEs) . "\n";
