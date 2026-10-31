<?php
$file = 'resources/js/components/warehouses/index.vue';
$content = file_get_contents($file);
$content = str_replace("\r\n", "\n", $content);
file_put_contents($file, $content);
echo "Normalized " . strlen($content) . " bytes\n";

