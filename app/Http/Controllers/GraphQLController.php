<?php

namespace App\Http\Controllers;

use App\GraphQL\AppResolver;
use App\GraphQL\AppSchema;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GraphQLController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $variableValues = $request->input('variables');
        $operationName = $request->input('operationName');

        if (is_string($variableValues)) {
            $variableValues = json_decode($variableValues, true) ?: [];
        }

        if (empty($query)) {
            return response()->json([
                'errors' => [
                    ['message' => 'GraphQL query is required.']
                ]
            ], 400);
        }

        try {
            $schema = AppSchema::build();
            $rootValue = AppResolver::getRootValue();
            $context = [
                'user' => $request->user(),
                'request' => $request,
            ];

            $debug = config('app.debug') ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE) : DebugFlag::NONE;
            $result = GraphQL::executeQuery(
                $schema,
                $query,
                $rootValue,
                $context,
                $variableValues,
                $operationName
            );

            $output = $result->toArray($debug);
            return response()->json($output);
        } catch (Throwable $e) {
            return response()->json([
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                        'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                    ]
                ]
            ], 500);
        }
    }
}

