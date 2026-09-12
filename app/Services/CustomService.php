<?php

namespace App\Services;

use Cloudstudio\Ollama\Facades\Ollama;

class CustomService
{
    /**
     * The model to use for AI generation.
     */
    private string $model;

    public function __construct()
    {
        // $this->model = env('OLLAMA_MODEL', 'gemma4:31b');
        // $this->model = env('OLLAMA_MODEL', 'gemini-3-flash-preview:cloud');
        $this->model = env('OLLAMA_MODEL', 'gemma4:31b-cloud');
        // $this->model = "gpt-oss:120b-cloud";
    }

    /**
     * Generate a response using the AI model.
     * Used for general-purpose prompts (SQL generation, data analysis, etc.)
     */
    public function generate(string $prompt_question): string|null
    {
        $response = Ollama::agent($this->getSystemPrompt())
            ->prompt($prompt_question)
            ->model($this->model)
            ->ask();
        try {
            return $this->cleanResponse($response['response'] ?? null);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a SQL query from a natural language question.
     * Applies stricter instructions to return ONLY raw SQL.
     */
    public function generateSQL(string $prompt): string|null
    {
        $response = Ollama::agent($this->getSQLSystemPrompt())
            ->prompt($prompt)
            ->model($this->model)
            ->ask();
        try {
            $sql = $response['response'] ?? null;
            return $this->cleanSQL($sql);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a human-friendly answer from data.
     * Used for the second-pass (data → explanation).
     */
    public function generateAnswer(string $prompt): string|null
    {
        $response = Ollama::agent($this->getAnswerSystemPrompt())
            ->prompt($prompt)
            ->model($this->model)
            ->ask();
        try {
            return $this->cleanResponse($response['response'] ?? null);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Classify user intent as read/create/delete.
     * Returns raw JSON string from the AI.
     */
    public function classifyIntent(string $prompt): string|null
    {
        $response = Ollama::agent($this->getIntentSystemPrompt())
            ->prompt($prompt)
            ->model($this->model)
            ->ask();
        try {
            return $this->cleanJSON($response['response'] ?? null);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * System prompt for the SQL generation role.
     */
    private function getSQLSystemPrompt(): string
    {
        return 'You are an expert MySQL query generator. '
            . 'You MUST return ONLY a single valid MySQL SELECT query. '
            . 'No markdown, no explanation, no backticks, no comments — just the raw SQL. '
            . 'Never use INSERT, UPDATE, DELETE, DROP, ALTER, or TRUNCATE. '
            . 'Always use read-only SELECT statements. '
            . 'If you are unsure, return: SELECT 1 AS no_data';
    }

    /**
     * System prompt for the answer/analysis role.
     */
    private function getAnswerSystemPrompt(): string
    {
        return 'You are a professional data analyst AI assistant for a fish management ERP system called "Blue Helm Harvest", developed by ELBACHIR NOUBAA. '
            . 'You help users understand their business data with clear, accurate answers. '
            . 'Rules: '
            . '1) NEVER invent or fake data. If data is empty say "No data available". '
            . '2) Detect the user language and answer in the same language. '
            . '3) Format all numbers like: 3 000.00 (space as thousand separator, dot as decimal). '
            . '4) For simple answers, use plain text. For complex data, use clean HTML with inline styles. '
            . '5) When showing tables, use modern styled HTML tables with borders. '
            . '6) For charts, use ApexCharts JS code wrapped in a div. '
            . '7) Be concise and professional.'
            . '8). don\'t get users conversation history.';
    }

    /**
     * General system prompt (fallback).
     */
    private function getSystemPrompt(): string
    {
        return 'You are an AI assistant for a fish management ERP application. '
            . 'Answer questions accurately without hallucinations. '
            . 'Give clean, minimal responses.';
    }

    /**
     * System prompt for intent classification (read vs create vs delete).
     */
    private function getIntentSystemPrompt(): string
    {
        return 'You are a strict JSON-only intent classifier. You MUST return ONLY raw JSON. No text, no markdown, no explanation. '
            . 'Classify the user message into one of these intents: '
            . '"read" — user wants to query, view, list, count, or analyze data. '
            . '"create" — user wants to add, create, insert, register, or entry new records. KEY WORDS: create, add, insert, entry, register, new, enregistrer, ajouter, créer. '
            . '"delete" — user wants to delete, remove, or suppress records. KEY WORDS: delete, remove, suppress, supprimer. '
            . '"confirm" — user is saying yes, ok, confirm, go ahead, oui, d\'accord to a previous action proposal. '
            . '"cancel" — user is saying no, cancel, stop, annuler to a previous action proposal. '
            . 'IMPORTANT: There is NO record limit. If the user asks to create 20, 50, 100, or any number of records, you MUST generate ALL of them in the "records" array. Never truncate, summarize, or use ellipsis. Generate every single record. '
            . 'EXAMPLES: '
            . 'User: "How many charges this month?" → {"intent":"read"} '
            . 'User: "Create a section named emballage" → {"intent":"create","actions":[{"model":"Section","records":[{"name":"emballage"}]}],"pre_create":[],"missing_info":[]} '
            . 'User: "Add 20 workforces" → intent is "create" with 20 complete records in the records array, each with realistic unique names. '
            . 'User: "Add activity for workforce 1000 price 150" → {"intent":"create","actions":[{"model":"FortnightActivity","records":[{"workforce_id":1000,"price":150}]}],"pre_create":[],"missing_info":["date","section_id"]} '
            . 'User: "Delete supplier with id 5" → {"intent":"delete","actions":[{"model":"Supplier","ids":[5],"description":"Supplier #5"}]} '
            . 'User: "Delete the ship named Atlantic" → {"intent":"delete","actions":[{"model":"Ship","ids":[],"description":"Ship Atlantic","lookup":{"field":"name","value":"Atlantic"}}} '
            . 'User: "yes" → {"intent":"confirm"} '
            . 'User: "no" → {"intent":"cancel"} '
            . 'IMPORTANT: When the user uses words like "create", "add", "insert", "entry", "register", "new", you MUST return intent "create", NOT "read". '
            . 'Return ONLY the JSON object. Nothing else.';
    }

    /**
     * Clean SQL output — strip markdown fences, trim whitespace.
     */
    private function cleanSQL(?string $sql): ?string
    {
        if (!$sql)
            return null;
        $sql = trim($sql);
        // Remove markdown code fences
        $sql = preg_replace('/^```(?:sql|mysql)?\s*/i', '', $sql);
        $sql = preg_replace('/\s*```$/', '', $sql);
        $sql = trim($sql);
        // Remove trailing semicolons (DB::select doesn't need them)
        $sql = rtrim($sql, ';');
        return $sql ?: null;
    }

    /**
     * Clean general response — trim, remove empty wrappers.
     */
    private function cleanResponse(?string $text): ?string
    {
        if (!$text)
            return null;
        $text = trim($text);
        // Remove wrapping ```html fences if present
        $text = preg_replace('/^```(?:html)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        return trim($text) ?: null;
    }

    /**
     * Clean JSON output — strip markdown fences, extract JSON object.
     */
    private function cleanJSON(?string $text): ?string
    {
        if (!$text)
            return null;
        $text = trim($text);
        // Remove markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = trim($text);
        // Extract first JSON object
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            return $m[0];
        }
        return $text ?: null;
    }
}
