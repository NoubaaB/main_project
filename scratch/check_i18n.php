<?php
$content1 = file_get_contents('resources/js/components/warehouses/index.vue');
$content2 = file_get_contents('resources/js/components/warehouses/script.js');

preg_match_all("/tLabel\(\s*['\"]([^'\"]+)['\"]/m", $content1 . $content2, $m1);
preg_match_all("/\\\$t\(\s*['\"]([^'\"]+)['\"]/m", $content1 . $content2, $m2);
$keys = array_unique(array_merge($m1[1], $m2[1]));
sort($keys);
echo 'Found ' . count($keys) . ' translation keys in View 6 / warehouses.' . PHP_EOL;

$locales = [
    'res_fr' => 'resources/js/locales/fr/app.json',
    'res_en' => 'resources/js/locales/en/app.json',
    'res_es' => 'resources/js/locales/es/app.json',
    'loc_fr' => 'locals/fr.json',
    'loc_en' => 'locals/en.json',
    'loc_es' => 'locals/es.json',
];

$dicts = [];
foreach ($locales as $name => $path) {
    if (file_exists($path)) {
        $dicts[$name] = json_decode(file_get_contents($path), true) ?: [];
    } else {
        echo 'Missing file: ' . $path . PHP_EOL;
    }
}

$missing = [];
foreach ($keys as $k) {
    foreach ($dicts as $dname => $dict) {
        if (!isset($dict[$k])) {
            $missing[$dname][] = $k;
        }
    }
}

foreach ($missing as $dname => $mkeys) {
    echo $dname . ' is missing ' . count($mkeys) . ' keys:' . PHP_EOL;
    foreach ($mkeys as $mk) {
        echo "  - $mk" . PHP_EOL;
    }
}

