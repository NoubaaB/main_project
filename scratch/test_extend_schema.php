<?php

require __DIR__ . '/../vendor/autoload.php';

use GraphQL\Utils\BuildSchema;

$sdlPart1 = <<<GQL
type Query {
    hello: String
}
GQL;

$sdlPart2 = <<<GQL
extend type Query {
    world: String
}
GQL;

try {
    $schema = BuildSchema::build($sdlPart1 . "\n" . $sdlPart2);
    echo "Extend type Query works in webonyx/graphql-php!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

