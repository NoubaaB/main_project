<?php

$vueDir = realpath(__DIR__ . '/../resources/js');
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vueDir));
$bugs = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'vue') continue;

    $content = file_get_contents($file->getPathname());
    $rel = str_replace($vueDir, '', $file->getPathname());

    // Match attributes like label="$t(...)" or placeholder="$t(...)" without colon
    if (preg_match_all('/\s([a-zA-Z0-9_\-]+)=["\'](\$t\([^"\']+\))["\']/m', $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $idx => $match) {
            $attr = $m[1][$idx][0];
            $val = $m[2][$idx][0];
            // Check if preceding char was ':'
            $offset = $m[0][$idx][1];
            $charBefore = substr($content, $offset - 1, 1);
            if ($charBefore !== ':') {
                $bugs[] = [
                    'file' => $rel,
                    'attr' => $attr,
                    'val' => $val,
                    'full' => $match[0]
                ];
            }
        }
    }
}

echo "Found " . count($bugs) . " unescaped literal \$t attribute bindings:\n";
foreach ($bugs as $b) {
    echo "  {$b['file']}: {$b['attr']}=\"{$b['val']}\"\n";
}

