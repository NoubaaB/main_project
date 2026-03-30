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

class AIQueryController extends Controller
{
    private $ai_agent = null;

    public function __construct() {
        // $this->ai_agent = new GeminiService();
        $this->ai_agent = new CustomService();
    }
    
    public function ask(Request $request)
    {
        // $question = "give me data table and column chart me commands and date and tips and total in the last 30 days format numbers to readable format";
        // $question = "get all commands and analyse it and tell me what is the month that I lose the money and what is the month that i gain the money and what is the month that i have a lot of commands and what is the month that i have few commands";
        // $question = "give me Chart with Column with Data Labels of all days total commands and total tips and total globally in the last 15 days in MAD and format numbers to must be readable";
        $data = $request->validate([
            'message' => 'required|string',
            'id' => 'required|numeric',
            'theme' => 'required|string',
            'messages' => 'required|string',
        ]);
        $question = $data['message'];
        $id = $data['id'];
        $theme = $data['theme'];
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

            User question: {$question} , and use previous conversation that you and user have made to understand the context : {$messages}.
        ";

        $sqlQuery = $this->ai_agent->generate($text);
        
        if (!$sqlQuery) {
            return $sqlQuery;
        }
        // Execute the SQL query safely
        $answer = null;
        $result = null;
        try{
            $result = DB::select($sqlQuery);
            $result = json_encode($result);
        }catch(\Exception $e){
        }
        // Get all table names from DB
    
        $text = "
            You are a Moroccan chartered accountant and a professional SQL data analyst and a frontend developer, developed by ELBACHIR NOUBAA.
            don't fake data if local data is empty , if data is empty or NULL just tell user in proper way data is not available.
            You are provided with the database schema and table relationships: {$schema}
            Based on:
            - local data that you already selected: [$result]
            - and provided also with database tables Comments that help you to understand relation and purpose of each table and take right decision : {$table_comments}

            Answer the question: ['$question']. and use previous conversation that you and user have made to understand the context : {$messages}.

            Instructions:
            * consider that already html file:
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <link href='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.css' rel='stylesheet'>
                <link href='https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css' rel='stylesheet'>
                <script src='https://unpkg.com/vue@3/dist/vue.global.js'></script>
                <script src='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.js'></script>
                <script src='https://cdn.jsdelivr.net/npm/apexcharts'></script>
                <script type='importmap'>
                    {
                        'imports': {
                            'vue': 'https://cdn.jsdelivr.net/npm/vue@latest/dist/vue.esm-browser.js',
                            'vuetify': 'https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.esm.js'
                        }
                    }
                </script>

            </head>

            <body>
                <div id='app'></div>
                @vite(['resources/js/app.js'])

                <script>
                    const {
                        createApp
                    } = Vue
                    const {
                        createVuetify
                    } = Vuetify

                    const vuetify = createVuetify();
                </script>
            </body>

            </html>
            * please prevent use v-table in vue js code this is most important
            *do not instace vuetify again in your code
            *do not instace vue js again in your code
            *do not use any other library or framework
            *do not use any HTTP clients or HTTP request libraries like fetch or axios or call any URL
            * when use Apexchart use it as <div id='aichart_$id'></div> with id *do not use <apexchart/> component*, use random id to identifier 
            *then start code like this:
            <div id='ai_$id'><v-containter class='bg-white rounded-xl'> /* write vuetify and vue js code here */ </v-container></div>
            <script> createApp({ //write vue code here; })use(vuetify).mount('#ai_$id') </script>
            * make sure that vue data properties match with what render in template vue in option API
            * make sure to make any number has relation with moneytize to be like this example '100.00'
            * Use Moroccan Dirham (MAD) for any money values and format any nymber like this example '3 000.00' .
            Give me only the body content of an HTML file using Vue 3 and Vuetify 3 via CDN — no <html>, <head>, or styles — just the <div id='app'>, the CDN <script> tags, the Vue template in <script type='text / x - template'>, and the JavaScript to mount the app.             
            and finally make sure if code work properly without any error
            *do not instance vue js again in your code
            *do not instance vuetify again in your code like createApp or createVuetify
            *do not use any other library or framework
            *do not use v-table use v-data-table instead
            *do not use <body> tag
            *do not use v-chart use ApexCharts v5.3.5 instead
            *implement theme $theme mode
        ";
        if ($result == null) {
            $text = "
                you are AI agent developed by ELBACHIR NOUBAA, be free to Answer the question: '$question', and use previous conversation that you and user have made to understand the context : {$messages}.

                Instructions:
                * consider that already import those liberary in my html file:
                    <link href='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.css' rel='stylesheet'>
                    <link href='https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css' rel='stylesheet'>
                    <script src='https://unpkg.com/vue@3.4.32/dist/vue.global.js'></script>
                    <script src='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.js'></script>
                *consider also that I already instance vue js and vuetify in my html file like this:
                <script>
                    const { createApp } = Vue;
                    const vuetify = Vuetify.createVuetify();
                </script>
                *do not instace vuetify again in your code
                *do not instace vue js again in your code
                *do not use any other library or framework
                *do not use any HTTP clients or HTTP request libraries like fetch or axios or call any URL
                *then start code like this:
                <div id='ai_$id'><v-containter class='bg-white rounded-xl'> /* write vuetify and vue js code here */ </v-container></div>
                <script> createApp({ //write vue code here; })use(vuetify).mount('#ai_$id') </script>
                - make sure that vue data properties match with what render in template vue
                - make sure to make any number has relation with moneytize to be like this example '100.00'
                and finally make sure if code work properly without any error
            ";
        }

        //get final answer from ai
        $answer = $this->ai_agent->generate($text);

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
