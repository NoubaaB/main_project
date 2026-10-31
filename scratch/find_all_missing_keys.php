<?php

$vueDir = realpath(__DIR__ . '/../resources/js');
$enPath = __DIR__ . '/../resources/js/locales/en/app.json';
$frPath = __DIR__ . '/../resources/js/locales/fr/app.json';
$esPath = __DIR__ . '/../resources/js/locales/es/app.json';

$en = json_decode(file_get_contents($enPath), true) ?: [];
$fr = json_decode(file_get_contents($frPath), true) ?: [];
$es = json_decode(file_get_contents($esPath), true) ?: [];

$allExistingKeys = array_unique(array_merge(array_keys($en), array_keys($fr), array_keys($es)));

// Scan all vue/js files
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
$missingKeys = [];

$regexes = [
    '/(?:\$t|this\.\$t|t|\$te|\$tc|i18n\.t)\(\s*[\'"]([a-zA-Z0-9_\-\.\:]+)[\'"]/m',
    '/\b(?:label|title|placeholder|subtitle|header|tooltip):\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/m',
    '/headers.*?key:\s*[\'"]([a-zA-Z0-9_]+)[\'"].*?title:\s*[\'"]([a-zA-Z0-9_]+)[\'"]/s',
    '/\b(?:trans|__)\(\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]/'
];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $ext = $file->getExtension();
    if (!in_array($ext, ['vue', 'js', 'ts'])) continue;

    $content = file_get_contents($file->getPathname());
    $rel = str_replace($vueDir, '', $file->getPathname());

    foreach ($regexes as $reg) {
        if (preg_match_all($reg, $content, $matches)) {
            $matchedKeys = isset($matches[2]) && count($matches[2]) > 0 ? array_merge($matches[1], $matches[2]) : $matches[1];
            foreach ($matchedKeys as $k) {
                $k = trim($k);
                // Filter out non-translation identifiers
                if (strlen($k) < 2 || is_numeric($k) || in_array($k, ['true', 'false', 'null', 'undefined', 'get', 'post', 'put', 'delete', 'http', 'https', 'rgba', 'json', 'desc', 'asc', 'compact', 'outlined', 'default', 'small', 'large', 'x-small', 'x-large', 'center', 'left', 'right', 'top', 'bottom', 'start', 'end', 'px', 'em', 'rem'])) {
                    continue;
                }
                // Check if snake_case or word that is supposed to be translated
                if (!isset($en[$k]) && !isset($fr[$k]) && !isset($es[$k])) {
                    if (!isset($missingKeys[$k])) {
                        $missingKeys[$k] = [];
                    }
                    if (count($missingKeys[$k]) < 2) {
                        $missingKeys[$k][] = $rel;
                    }
                }
            }
        }
    }
}

echo "Found " . count($missingKeys) . " potential missing keys:\n";
$report = [];
foreach ($missingKeys as $k => $locs) {
    $report[$k] = $locs;
    echo "  - $k => " . implode(', ', $locs) . "\n";
}

file_put_contents(__DIR__ . '/missing_keys_report.json', json_encode($report, JSON_PRETTY_PRINT));

