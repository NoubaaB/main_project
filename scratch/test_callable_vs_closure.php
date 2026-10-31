<?php
require 'vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;

class TestController {
    public static function resolveItems($root, $args) {
        return [
            ['id' => 1, 'name' => 'Alpha'],
            ['id' => 2, 'name' => 'Beta']
        ];
    }
}

$schema = BuildSchema::build('
type Query {
    itemsArrayCallable: [Item]
    itemsClosure: [Item]
}
type Item {
    id: ID!
    name: String
}
');

$rootValue = [
    'itemsArrayCallable' => [TestController::class, 'resolveItems'],
    'itemsClosure' => fn($root, $args) => TestController::resolveItems($root, $args),
];

echo "Testing itemsArrayCallable:\n";
$r1 = GraphQL::executeQuery($schema, '{ itemsArrayCallable { id name } }', $rootValue);
print_r($r1->toArray());

echo "\nTesting itemsClosure:\n";
$r2 = GraphQL::executeQuery($schema, '{ itemsClosure { id name } }', $rootValue);
print_r($r2->toArray());

