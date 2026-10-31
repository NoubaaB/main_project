<?php

$vueDir = __DIR__ . '/../resources/js/components';
$enPath = __DIR__ . '/../resources/js/locales/en/app.json';
$en = json_decode(file_get_contents($enPath), true) ?: [];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
$hardcodedPhrases = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'vue') continue;

    $content = file_get_contents($file->getPathname());
    $rel = str_replace(realpath($vueDir), '', realpath($file->getPathname()));

    // Find templates only
    if (preg_match('/<template>([\s\S]*?)<\/template>/', $content, $tMatch)) {
        $template = $tMatch[1];

        // 1. Check title="..." or label="..." or placeholder="..." where not bound (:title, :label)
        if (preg_match_all('/\b(label|placeholder|title)=["\']([^"\'\{\}]+)["\']/', $template, $m)) {
            foreach ($m[2] as $idx => $val) {
                $attr = $m[1][$idx];
                $val = trim($val);
                if (strlen($val) > 2 && !preg_match('/^[a-z0-9_\-\.]+$/i', $val) && !str_starts_with($val, 'mdi-')) {
                    $keyGuess = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($val)));
                    if (!isset($en[$keyGuess]) && !isset($en[$val])) {
                        $hardcodedPhrases[$val] = [
                            'file' => $rel,
                            'type' => "attr:$attr",
                            'suggested_key' => $keyGuess
                        ];
                    }
                }
            }
        }

        // 2. Check tooltip content without $t
        if (preg_match_all('/<v-tooltip[^>]*>([^<\{\}]+)<\/v-tooltip>/', $template, $m)) {
            foreach ($m[1] as $val) {
                $val = trim($val);
                if (strlen($val) > 2) {
                    $keyGuess = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($val)));
                    if (!isset($en[$keyGuess]) && !isset($en[$val])) {
                        $hardcodedPhrases[$val] = [
                            'file' => $rel,
                            'type' => 'tooltip',
                            'suggested_key' => $keyGuess
                        ];
                    }
                }
            }
        }

        // 3. Check v-btn text without $t
        if (preg_match_all('/<v-btn[^>]*>\s*([A-Za-z][A-Za-z0-9\s]{2,30})\s*<\/v-btn>/', $template, $m)) {
            foreach ($m[1] as $val) {
                $val = trim($val);
                if (strlen($val) > 2 && !in_array(strtolower($val), ['true', 'false', 'null'])) {
                    $keyGuess = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($val)));
                    if (!isset($en[$keyGuess]) && !isset($en[$val])) {
                        $hardcodedPhrases[$val] = [
                            'file' => $rel,
                            'type' => 'button',
                            'suggested_key' => $keyGuess
                        ];
                    }
                }
            }
        }
    }
}

echo "Potentially untranslated template strings found: " . count($hardcodedPhrases) . "\n";
$i = 0;
foreach ($hardcodedPhrases as $val => $info) {
    echo "  - [{$info['type']}] \"$val\" in {$info['file']} -> suggested key: '{$info['suggested_key']}'\n";
    if (++$i >= 50) break;
}

