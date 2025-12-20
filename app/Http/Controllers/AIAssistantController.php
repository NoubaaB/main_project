<?php

namespace App\Http\Controllers;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\CustomService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AIAssistantController extends Controller
{
    private $gemini = null;

    public function __construct()
    {
        // $this->gemini = new GeminiService();
        $this->gemini = new CustomService();
    }

    public function ask(Request $request)
    {
        // $question = "give me data table and column chart me commands and date and tips and total in the last 30 days format numbers to readable format";
        // $question = "get all commands and analyse it and tell me what is the month that I lose the money and what is the month that i gain the money and what is the month that i have a lot of commands and what is the month that i have few commands";
        // $question = "give me Chart with Column with Data Labels of all days total commands and total tips and total globally in the last 15 days in MAD and format numbers to must be readable";
        $data = $request->validate([
            'message' => 'required|string',
            'messages' => 'required|string',
        ]);
        $question = $data['message'];
        $messages = $data['messages'];

        // Get all table names from DB
        $existingTables = $this->getDatabaseSchema();
        $table_comments = $this->getTableComments();
        $schema = $this->getDatabaseSchema()->getContent(); // This gives JSON of schema
        // Generate SQL Query
        $text = "
            You are an expert SQL data generator and analyst.
            The application is a fish management system built with Laravel and MySQL.
            You are provided with the database schema and table relationships: {$existingTables}
            and provided also with database tables Comments that help you to understand relation and purpose of each table and take right decision : {$table_comments}

            Your task:
            - Internally analyze the schema and relationships step by step.
            - Do not show your reasoning.
            - Generate only one final output: a valid MySQL query that answers the user’s request.
            - Do not include markdown, explanations, or extra text — only return the raw SQL query.

            User question: {$question}, and use previous conversation that you and user have made to understand the context : {$messages}.
        ";

        $sqlQuery = $this->gemini->generate($text);
        if (!$sqlQuery) {
            return $sqlQuery;
        }
        // Execute the SQL query safely
        $answer = null;
        $result = null;
        try {
            $result = DB::select($sqlQuery);
            $result = json_encode($result);
        } catch (\Exception $e) {
        }
        // Get all table names from DB

        $text = "
            You are a Moroccan AI agent and a professional SQL data analyst.
            don't fake data if local data is empty , if data is empty or NULL just write 'no data Availabel'.
            You are provided with the database schema and table relationships: {$schema}
            Based on:
            - the local data: [$result]
            - and provided also with database tables Comments that help you to understand relation and purpose of each table and take right decision : {$table_comments}

            Answer the question: ['$question'] , and use previous conversation that you and user have made to understand the context : {$messages}..
            
            give me pure plain text without any comments, just response about the question 
        ";
        //get final answer from ai
        $answer = $this->gemini->generate($text);

        return response()->json(['answer' => $answer], 200);
    }


    public function getDatabaseSchema()
    {
        // Step 1: Get Database Schema (Tables & Columns)
        $tables = DB::select("SHOW TABLES");
        $tableNames = array_map('current', $tables);
        $schema = [];

        foreach ($tableNames as $table) {
            $columns = DB::select("SHOW COLUMNS FROM $table");
            $schema[$table]['columns'] = array_map(fn($column) => [
                "Field" => $column->Field,
                "Type" => $column->Type
            ], $columns);
        }

        // Step 2: Get Model Relationships with Type
        $models = $this->getAllModels();
        foreach ($models as $model) {
            $modelInstance = new $model();
            $table = $modelInstance->getTable();
            $schema[$table]['relationships'] = $this->getModelRelationships($modelInstance);
        }
        return response()->json($schema, Response::HTTP_OK, [], JSON_PRETTY_PRINT);
    }

    // Function to get all model classes dynamically
    private function getAllModels()
    {
        $modelPath = app_path('Models'); // Update if models are in a different path
        $models = [];

        if (File::exists($modelPath)) {
            foreach (File::allFiles($modelPath) as $file) {
                $namespace = "App\\Models\\";
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $fullClass = $namespace . $className;

                if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                    $models[] = $fullClass;
                }
            }
        }

        return $models;
    }

    // Function to get all public relationship methods with their type
    private function getModelRelationships($modelInstance)
    {
        $reflection = new ReflectionClass($modelInstance);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $relationships = [];

        foreach ($methods as $method) {
            if ($method->class === get_class($modelInstance) && $method->getNumberOfParameters() === 0) {
                try {
                    $relation = $method->invoke($modelInstance);
                    if ($relation instanceof Relation) {
                        $relationships[$method->name] = class_basename(get_class($relation));
                    }
                } catch (\Throwable $e) {
                    // Ignore methods that throw errors
                }
            }
        }

        return $relationships;
    }

    function getTableComments()
    {
        $database = DB::getDatabaseName();
        $tablesWithComments = DB::table('information_schema.tables')
            ->select('table_name', 'table_comment')
            ->where('table_schema', $database)
            ->get();
        return $tablesWithComments;
    }
}
