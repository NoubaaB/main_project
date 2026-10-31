<?php

$locales = ['en', 'fr', 'es'];
$translations = [];
foreach ($locales as $l) {
    $translations[$l] = json_decode(file_get_contents(__DIR__ . "/../locals/{$l}.json"), true) ?: [];
}

$files = [
    __DIR__ . '/../resources/js/components/schema/NavigationDrawer.vue',
    __DIR__ . '/../resources/js/components/schema/CommandPalette.vue',
    __DIR__ . '/../resources/js/utils/routeMetadata.js',
    __DIR__ . '/../resources/js/router/routes.js',
];

$keysFound = [];
foreach ($files as $file) {
    $c = file_get_contents($file);
    if (preg_match_all('/(?:this\.\$t|(?<=[^a-zA-Z0-9_\$])t)\(\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\)/', $c, $m)) {
        foreach ($m[1] as $k) $keysFound[$k] = $file;
    }
    if (preg_match_all('/(?:titleKey|labelKey|key):\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/', $c, $m)) {
        foreach ($m[1] as $k) $keysFound[$k] = $file;
    }
}

$missing = [];
foreach ($keysFound as $k => $f) {
    if (!isset($translations['en'][$k]) || $translations['en'][$k] === '') {
        $missing[$k] = basename($f);
    }
}

echo "Found " . count($keysFound) . " navigation/route keys.\n";
echo "Missing: " . count($missing) . "\n";
foreach ($missing as $k => $f) {
    echo "  - {$k} (in {$f})\n";
}

