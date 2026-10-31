<?php
require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;

$schemaString = <<<GQL
type Query {
    hello(name: String): String
}
GQL;

$rootValue = [
    'hello' => function($root, $args) {
        $name = $args['name'] ?? 'World';
        return "Hello, {$name} from GraphQL!";
    }
];

$schema = BuildSchema::build($schemaString);
$query = '{ hello(name: "Warehouse") }';

$result = GraphQL::executeQuery($schema, $query, $rootValue);
$output = $result->toArray();

echo json_encode($output, JSON_PRETTY_PRINT);

