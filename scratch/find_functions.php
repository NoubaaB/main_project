<?php
$lines = file(__DIR__ . '/../app/Console/Commands/MigrateVillamarToJupiterCommand.php');
foreach ($lines as $num => $l) {
    if (preg_match('/function\s+(\w+)/', $l, $m)) {
        echo ($num + 1) . ': ' . $m[1] . PHP_EOL;
    }
}
