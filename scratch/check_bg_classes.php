<?php
$content = file_get_contents('resources/js/components/warehouses/index.vue');
$pos = strpos($content, 'VIEW 6');
$view6 = substr($content, $pos, 45000);
preg_match_all('/class=[\'"][^\'"]*bg-[^\'"]*[\'"]/', $view6, $matches);
echo "Unique bg- classes in View 6:\n";
foreach (array_unique($matches[0]) as $m) {
    echo "  " . $m . PHP_EOL;
}

