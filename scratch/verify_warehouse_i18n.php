<?php

$vue = file_get_contents(__DIR__ . '/../resources/js/components/warehouses/index.vue');
$js = file_get_contents(__DIR__ . '/../resources/js/components/warehouses/script.js');
$content = $vue . ' ' . $js;

// Match tLabel('key', ...), $t('key'), t('key')
preg_match_all('/(?:tLabel|\$t|\bt)\s*\(\s*[\'"]([a-zA-Z0-9_\.\-]+)[\'"]/', $content, $matches);
$keys = array_values(array_unique($matches[1]));

$langs = ['en', 'es', 'fr'];
foreach ($langs as $lang) {
    $localJson = json_decode(file_get_contents(__DIR__ . "/../locals/{$lang}.json"), true) ?: [];
    $appJson = json_decode(file_get_contents(__DIR__ . "/../resources/js/locales/{$lang}/app.json"), true) ?: [];
    
    $missingLocals = [];
    $missingApp = [];
    foreach ($keys as $k) {
        if (!isset($localJson[$k])) {
            $missingLocals[] = $k;
        }
        if (!isset($appJson[$k])) {
            $missingApp[] = $k;
        }
    }
    echo "Language {$lang}: Total keys: " . count($keys) . "\n";
    echo "  Missing in locals/{$lang}.json: " . count($missingLocals) . "\n";
    if (count($missingLocals) > 0) {
        echo "    Missing: " . implode(', ', array_slice($missingLocals, 0, 15)) . "\n";
    }
    echo "  Missing in app.json: " . count($missingApp) . "\n";
    if (count($missingApp) > 0) {
        echo "    Missing: " . implode(', ', array_slice($missingApp, 0, 15)) . "\n";
    }
}
