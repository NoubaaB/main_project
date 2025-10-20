<?php

namespace App\Http\Controllers;

use ReflectionClass;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class DeepSeekController extends Controller
{
    public function ask(Request $request)
    {
        // $question = "give me data table and column chart me commands and date and tips and total in the last 30 days format numbers to readable format";
        // $question = "get all commands and analyse it and tell me what is the month that I lose the money and what is the month that i gain the money and what is the month that i have a lot of commands and what is the month that i have few commands";
        // $question = "give me Chart with Column with Data Labels of all days total commands and total tips and total globally in the last 15 days in MAD and format numbers to must be readable";
        $data = $request->validate([
            'message' => 'required|string',
            'id' => 'required|numeric',
            'theme' => 'required|string',
        ]);
        $question = $data['message'];
        $id = $data['id'];
        $theme = $data['theme'];

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
            - Ensure the SQL query is syntactically correct and executable.
            - Use table and column names exactly as they appear in the schema.
            - give me only one SQL query that is the most efficient to get the data ( pure sql without any comments or markdown).
            User question: {$question}
        ";

        $sqlQuery = $this->ask_ai($text);

        if (!$sqlQuery) {
            return response()->json(['error' => 'Failed to generate SQL query'], 500);
        }
        // Execute the SQL query safely
        $answer = null;
        $result = null;
        try {
            $result = DB::select($sqlQuery);
            $result = response()->json($result, 200, [], JSON_PRETTY_PRINT) ?? null;
        } catch (\Exception $e) {
        }
        // Get all table names from DB
        $text = "
                You are a professional SQL data analyst and frontend developer.

                Context variables (will be provided by the caller):
                - $schema          => the database schema and table relationships (string).
                - $result          => the local data rows (JSON array or object) retrieved from the database.
                - $table_comments  => comments/descriptions for each table to explain relation and purpose (string).
                - $question        => the user's natural language question (string).
                - $id              => a short unique id (alphanumeric) that must be used to namespace DOM ids.

                Primary goal:
                Produce a single HTML *body fragment* only (no <html>, <head>, or <body> tags). The output must be pure HTML + inline <script> needed to mount a Vue 3 (Option API) component that uses the existing Vuetify instance on the page. The fragment will be injected into an existing HTML file that already includes Vue 3 and Vuetify and already defines `const vuetify = createVuetify();` and exposes `Vue` globals.

                High-level rules (strict — follow them exactly):
                1. OUTPUT: Return **only** the body fragment: a top-level `<div id='ai_$id'>...</div>` followed by a single `<script>` that mounts the component. No extra text, no markdown, no explanations.
                2. START exactly like this (replace $id with provided id):  
                `<div id='ai_$id'><v-container class='bg-white rounded-xl'> ... </v-container></div>`  
                `<script> createApp({ /* Option API */ }).use(vuetify).mount('#ai_$id') </script>`
                - You may use `createApp` here, but **do not** call `createVuetify()` or re-import Vue or Vuetify. Use the existing global `vuetify`.
                3. Do **not** include CDN `<script>` or `<link>` tags — the host page already provides them.
                4. Use **Vue 3 Option API** (data, computed, methods, mounted). Template must bind exactly to data properties.
                5. Do **not** instantiate Vue or Vuetify globally beyond the final `createApp(...).use(vuetify).mount(...)` call.
                6. Do **not** use any HTTP calls, fetch/axios, or call any URL.
                7. Do **not** use any other library or framework.
                8. Do **not** use `<apexchart/>` or any 3rd-party Vue chart wrapper. If you need a chart, render a plain `<div id='aichart_$id'></div>` and initialize ApexCharts inside `mounted()` by selecting that id. Use ApexCharts v5 style JS initialization.
                9. Do **not** use `v-table`. Use `v-data-table` if a table is required.
                10. When presenting any numeric values that represent money, format them in Moroccan Dirham (MAD) and format numbers with a space as thousands separator and two decimals, for example: `3 000.00 MAD`. All other numbers should follow the same thousands-space and two-decimal format (e.g., `1 234.50`).
                11. If any required field is `null`, missing, or empty, do **not** invent values — render the exact text: `no data available`.
                12. Ensure all DOM ids are namespaced using `$id` (e.g., `ai_$id`, `aichart_$id`) so the fragment is safe to inject multiple times.
                13. Use only Vuetify components available in the page. Keep markup semantically clean and accessible.
                14. Make sure the template's bindings exactly match the `data()` structure (no undeclared reactive props).
                15. Ensure the generated JS has no syntax errors and would run in a browser environment where Vue 3 and Vuetify are already loaded and a global `vuetify` variable exists.

                Behavioral requirements (how to interpret inputs and produce output):
                - Read `$schema` and `$table_comments` to understand relationships and field meanings. Use them to pick the best fields from `$result`.
                - Answer `$question` by producing HTML that answers it visually (cards, lists, v-data-table, or charts). Focus on clarity and brevity.
                - If `$result` is empty or contains no rows relevant to the question, render a simple informative card with the text `no data available`.
                - All textual content is plain HTML; do not include any analytics, remote logging, or inline event handlers that call external URLs.
                - If showing currency or totals, compute them from provided `$result` and format as specified (MAD with thousands-space and two decimals). Do not fabricate totals — compute them only from the provided `$result`.
                - If you produce a chart, the chart must be initialized in `mounted()` using ApexCharts by selecting the `#aichart_$id` element and calling `new ApexCharts(...).render()`. The options must be basic and inline; do not call external resources.

                Template & JS structure required (exact pattern):
                - Top-level wrapper: `<div id='ai_$id'><v-container class='bg-white rounded-xl pa-4'> ... </v-container></div>`
                - After wrapper, a single `<script>` that contains:
                - `createApp({`  
                    `  data(){ return { /* reactive properties used by template */ } },`  
                    `  computed: { ... },`  
                    `  methods: { ... },`  
                    `  mounted(){ /* apexcharts init if needed */ }`  
                    `}).use(vuetify).mount('#ai_$id')`
                - Keep the script compact and only include code required to render the UI for the question.

                Small examples of correct patterns (the model should follow these patterns, not print them literally in the answer):
                - Chart container id: `<div id='aichart_$id'></div>` and then inside mounted: `const chart = new ApexCharts(document.querySelector('#aichart_$id'), options); chart.render();`
                - Money example rendered as: `<div>{{ formatMoney(row.total) }}</div>` where `formatMoney` produces `'3 000.00 MAD'`.

                Edge cases:
                - If `$result` contains nested relationships, flatten only what is necessary to answer `$question`. If a relation field is missing, show `no data available` for that cell.
                - If `$question` asks for SQL, return only the UI HTML fragment that displays the SQL and results (do not execute SQL).
                - If more than one visualization might answer the question, prefer a simple set of Vuetify cards + a small table; add a chart only if it adds real value.

                Final strict output instruction:
                - Produce **only** the HTML fragment described above (wrapper + script). Nothing else. No commentary, no debug logs, no extra whitespace outside the fragment.

                Now: using the variables provided ($schema, $result, $table_comments, $question, $id), generate the body fragment that answers $question and follows all rules above.
        ";
        if ($result == null) {
            $text = "
                be free to Answer the question: '$question'.

                Instructions:
                * consider that code is already exist in html file:'
                    <link href='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.css' rel='stylesheet'>
                    <link href='https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css' rel='stylesheet'>
                    <script src='https://unpkg.com/vue@3.4.32/dist/vue.global.js'></script>
                    <script src='https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.js'></script>
                    <script>
                        const { createApp } = Vue;
                        const vuetify = Vuetify.createVuetify();
                    </script>'

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
        $answer = $this->ask_ai($text);

        return response()->json(['answer' => $answer], 200);
    }


    function ask_ai($prompt_question)
    {
        $response = Ollama::agent('You are an AI assistant connected to a Laravel app database. answer the question without any hallucinations or explaining give just clean code')
            ->prompt($prompt_question)
            ->model('deepseek-v3.1:671b-cloud')
            ->options([
                'temperature' => 0.8,  // More creative
                'top_p' => 0.9,
                'max_tokens' => 5000000
            ])
            ->ask();
        try {
            return  $response['response'];
        } catch (\Exception $e) {
            return dd($response);
        }
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
