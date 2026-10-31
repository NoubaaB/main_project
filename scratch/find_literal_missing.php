<?php

$vueDir = realpath(__DIR__ . '/../resources/js');
$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true) ?: [];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
$missingKeys = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $ext = $file->getExtension();
    if (!in_array($ext, ['vue', 'js'])) continue;

    $content = file_get_contents($file->getPathname());
    $rel = str_replace($vueDir, '', $file->getPathname());

    // Match $t('literal') or this.$t('literal') or t('literal')
    if (preg_match_all('/(?:\$t|this\.\$t|\bt|\$te)\(\s*([\'"`])([^\'"`\$\{\}\+]+)\1/m', $content, $m)) {
        foreach ($m[2] as $k) {
            $k = trim($k);
            if (strlen($k) > 1 && !is_numeric($k) && !isset($en[$k])) {
                if (!isset($missingKeys[$k])) {
                    $missingKeys[$k] = [];
                }
                $missingKeys[$k][] = $rel;
            }
        }
    }

    // Match dynamic string concatenations like $t('prefix_' + var)
    if (preg_match_all('/(?:\$t|this\.\$t|\bt|\$te)\(\s*([\'"`])([a-zA-Z0-9_]+_)\1\s*\+/m', $content, $m)) {
        foreach ($m[2] as $prefix) {
            // Find possible suffixes nearby or in the file
            // e.g. status, type, role, etc.
        }
    }
}

echo "Total literal missing \$t keys: " . count($missingKeys) . "\n\n";
foreach ($missingKeys as $k => $locs) {
    echo "  Key: '$k'\n    In: " . implode(', ', array_unique($locs)) . "\n";
}

