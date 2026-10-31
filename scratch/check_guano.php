<?php
$locales = [
    'res_fr' => 'resources/js/locales/fr/app.json',
    'res_en' => 'resources/js/locales/en/app.json',
    'res_es' => 'resources/js/locales/es/app.json',
    'loc_fr' => 'locals/fr.json',
    'loc_en' => 'locals/en.json',
    'loc_es' => 'locals/es.json',
];
foreach ($locales as $name => $path) {
    $d = json_decode(file_get_contents($path), true);
    echo $name . ': ' . ($d['stockable_guanotype'] ?? 'NOT SET') . ' | short: ' . ($d['stockable_guanotype_short'] ?? 'NOT SET') . PHP_EOL;
}

