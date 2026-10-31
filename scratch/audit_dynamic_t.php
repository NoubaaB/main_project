<?php

function getFiles($dir, $extensions = ['vue', 'js']) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
        if (in_array($ext, $extensions)) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

$files = getFiles(__DIR__ . "/../resources/js");

$dynamicCalls = [];
$pattern = '/(?:\$t|(?<=[^a-zA-Z0-9_\$])t|i18n\.global\.t)\(\s*([^)]+)\s*\)/';

foreach ($files as $file) {
    if (strpos($file, 'node_modules') !== false) continue;
    $content = file_get_contents($file);
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[1] as $arg) {
            $arg = trim($arg);
            // If it starts and ends with quotes without variables, it's a static key
            if (preg_match('/^[\'"][a-zA-Z0-9_\-\.]+[\'"]$/', $arg)) {
                continue;
            }
            $relFile = str_replace(realpath(__DIR__ . "/../") . DIRECTORY_SEPARATOR, '', $file);
            $dynamicCalls[] = [
                'file' => $relFile,
                'arg' => $arg
            ];
        }
    }
}

echo "Found " . count($dynamicCalls) . " dynamic t() calls:\n";
$uniqueArgs = [];
foreach ($dynamicCalls as $d) {
    $arg = $d['arg'];
    if (!isset($uniqueArgs[$arg])) {
        $uniqueArgs[$arg] = [];
    }
    $uniqueArgs[$arg][] = $d['file'];
}

foreach ($uniqueArgs as $arg => $fileList) {
    echo "  " . substr($arg, 0, 80) . " (in " . implode(', ', array_slice($fileList, 0, 2)) . ")\n";
}

