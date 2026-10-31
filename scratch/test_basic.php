<?php
require 'vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;
use GraphQL\Executor\Executor;

$schema = BuildSchema::build('
type Query {
    items: [Item]
}
type Item {
    id: ID!
    name: String
}
');

$rootValue = [
    'items' => function($root, $args) {
        return [
            ['id' => 1, 'name' => 'Alpha'],
            ['id' => 2, 'name' => 'Beta']
        ];
    }
];

$res = GraphQL::executeQuery($schema, '{ items { id name } }', $rootValue);
print_r($res->toArray());

