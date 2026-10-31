<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $schema = App\GraphQL\AppSchema::build(true);
    $rootValue = App\GraphQL\AppResolver::getRootValue();

    $mutation = '
        mutation {
            saveWorkforceAbsenceSetting(threshold_days: 10) {
                threshold_days
                message
            }
        }
    ';

    $res = GraphQL\GraphQL::executeQuery($schema, $mutation, $rootValue, ['user' => null]);
    $arr = $res->toArray();
    if (!empty($arr['errors'])) {
        echo "FAILED saveWorkforceAbsenceSetting: " . json_encode($arr['errors']) . "\n";
    } else {
        echo "PASSED saveWorkforceAbsenceSetting: " . json_encode($arr['data']) . "\n";
    }

    // Reset back to 7
    $reset = '
        mutation {
            saveWorkforceAbsenceSetting(threshold_days: 7) {
                threshold_days
                message
            }
        }
    ';
    GraphQL\GraphQL::executeQuery($schema, $reset, $rootValue, ['user' => null]);
    echo "RESET threshold back to 7\n";

} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

