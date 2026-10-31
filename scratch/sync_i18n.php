<?php
$files = [
    ['locals/en.json', 'resources/js/locales/en/app.json'],
    ['locals/es.json', 'resources/js/locales/es/app.json'],
    ['locals/fr.json', 'resources/js/locales/fr/app.json'],
];

foreach ($files as $pair) {
    $c1 = json_decode(file_get_contents($pair[0]), true) ?: [];
    $c2 = json_decode(file_get_contents($pair[1]), true) ?: [];
    $merged = array_merge($c1, $c2);
    file_put_contents($pair[0], json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($pair[1], json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Synced {$pair[0]} & {$pair[1]}: " . count($merged) . " keys\n";
}

