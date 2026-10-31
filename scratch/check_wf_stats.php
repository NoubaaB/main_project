<?php

$content = file_get_contents(__DIR__ . '/../resources/js/components/workforce/WorkforceStatistics/index.vue');
if (preg_match_all('/label="\$t\([^"]*\)"/', $content, $m)) {
    foreach ($m[0] as $match) {
        echo "Found: $match\n";
    }
} else {
    echo "None found\n";
}

