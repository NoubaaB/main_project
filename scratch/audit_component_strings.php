<?php

$locales = ['en', 'fr', 'es'];
$translations = [];
foreach ($locales as $l) {
    $translations[$l] = json_decode(file_get_contents(__DIR__ . "/../locals/{$l}.json"), true) ?: [];
}

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../resources/js/components'));
foreach ($iterator as $f) {
    if ($f->isDir()) continue;
    $ext = pathinfo($f->getPathname(), PATHINFO_EXTENSION);
    if (in_array($ext, ['vue', 'js'])) $files[] = $f->getPathname();
}

// 1. Check add_snackbar calls with hardcoded strings
$hardcodedSnackbars = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/add_snackbar\(\s*\{[^\}]*message:\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
        foreach ($m[1] as $msg) {
            // Check if it's not a translation call
            $hardcodedSnackbars[] = [
                'file' => str_replace(realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR, '', $file),
                'msg' => $msg
            ];
        }
    }
}

echo "Found " . count($hardcodedSnackbars) . " hardcoded snackbars:\n";
foreach ($hardcodedSnackbars as $h) {
    echo "  - {$h['msg']} (in {$h['file']})\n";
}

