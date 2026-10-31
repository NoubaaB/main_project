<?php

$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true) ?: [];
$content = file_get_contents(__DIR__ . '/../resources/js/components/auth/ProfileSettings/index.vue');

preg_match_all('/(?:\$t|this\.\$t)\(\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/m', $content, $m);

$missing = [];
foreach ($m[1] as $k) {
    if (!isset($en[$k])) {
        $missing[] = $k;
    }
}

echo "Missing in ProfileSettings: " . count(array_unique($missing)) . "\n";
foreach (array_unique($missing) as $k) {
    echo "  - $k\n";
}

