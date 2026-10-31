<?php

$locales = ['en', 'fr', 'es'];
$translations = [];

foreach ($locales as $l) {
    $path = __DIR__ . "/../locals/{$l}.json";
    if (file_exists($path)) {
        $translations[$l] = json_decode(file_get_contents($path), true) ?: [];
    } else {
        $translations[$l] = [];
    }
}

// Function to scan directory recursively
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

$allKeysFound = [];
$keyLocations = [];

$patterns = [
    '/\$t\(\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]\s*[\),]/',
    '/(?:^|[^a-zA-Z0-9_\$])t\(\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]\s*[\),]/',
    '/i18n\.global\.t\(\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]\s*[\),]/',
    '/i18n\.t\(\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]\s*[\),]/',
    '/titleKey:\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]/',
    '/labelKey:\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]/',
    '/descKey:\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]/',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    // skip node_modules if any
    if (strpos($file, 'node_modules') !== false) continue;

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $k) {
                // Ignore dynamic expressions or common false positives like 'YYYY-MM-DD'
                if (empty($k) || is_numeric($k) || strlen($k) < 2) continue;
                if (in_array($k, ['YYYY-MM-DD', 'HH:mm', 'HH:mm:ss', 'true', 'false', 'null'])) continue;

                $allKeysFound[$k] = true;
                if (!isset($keyLocations[$k])) {
                    $keyLocations[$k] = [];
                }
                $relFile = str_replace(realpath(__DIR__ . "/../") . DIRECTORY_SEPARATOR, '', $file);
                if (!in_array($relFile, $keyLocations[$k])) {
                    $keyLocations[$k][] = $relFile;
                }
            }
        }
    }
}

ksort($allKeysFound);
echo "Total unique translation keys extracted from resources/js: " . count($allKeysFound) . "\n\n";

$missingPerLocale = ['en' => [], 'fr' => [], 'es' => []];

foreach ($allKeysFound as $k => $_) {
    foreach ($locales as $l) {
        if (!isset($translations[$l][$k]) || $translations[$l][$k] === '') {
            $missingPerLocale[$l][$k] = $keyLocations[$k];
        }
    }
}

foreach ($locales as $l) {
    echo "Missing in [{$l}]: " . count($missingPerLocale[$l]) . " keys\n";
}

echo "\n--- SAMPLE MISSING KEYS IN EN ---\n";
$sampleEn = array_slice($missingPerLocale['en'], 0, 40, true);
foreach ($sampleEn as $k => $locs) {
    echo "  - {$k} (in " . implode(', ', array_slice($locs, 0, 2)) . ")\n";
}

echo "\n--- SAMPLE MISSING KEYS IN FR ---\n";
$sampleFr = array_slice($missingPerLocale['fr'], 0, 40, true);
foreach ($sampleFr as $k => $locs) {
    echo "  - {$k} (in " . implode(', ', array_slice($locs, 0, 2)) . ")\n";
}

echo "\n--- SAMPLE MISSING KEYS IN ES ---\n";
$sampleEs = array_slice($missingPerLocale['es'], 0, 40, true);
foreach ($sampleEs as $k => $locs) {
    echo "  - {$k} (in " . implode(', ', array_slice($locs, 0, 2)) . ")\n";
}

// Dump full json of missing keys to a file for review
file_put_contents(__DIR__ . '/missing_i18n_report.json', json_encode([
    'missing_en' => array_keys($missingPerLocale['en']),
    'missing_fr' => array_keys($missingPerLocale['fr']),
    'missing_es' => array_keys($missingPerLocale['es']),
    'locations' => $keyLocations,
], JSON_PRETTY_PRINT));

