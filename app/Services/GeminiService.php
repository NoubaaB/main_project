<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private ?string $geminiApiKey;
    private Client $client;

    /**
     * Primary Gemini model to use.
     */
    private string $model;

    /**
     * Track which model actually produced the latest response.
     */
    private ?string $lastUsedModel = null;

    /**
     * Thinking/reasoning level: low, medium, high.
     */
    private string $thinkingLevel = 'high';

    /**
     * Fallback sequence of high-performance models with active quotas.
     */
    private array $fallbackModels = [
        'gemini-3.5-flash-lite',
        'gemini-3.1-flash-lite',
        'gemini-flash-lite-latest',
    ];

    public function __construct()
    {
        $this->geminiApiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-4-carbon');

        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout' => 30,
            'connect_timeout' => 8,
            'verify' => false,
        ]);
    }

    /**
     * Set the thinking level (low, medium, high).
     */
    public function setThinkingLevel(string $level): self
    {
        if (in_array(strtolower($level), ['low', 'medium', 'high', 'true', '1'])) {
            $this->thinkingLevel = strtolower($level);
        }
        return $this;
    }

    /**
     * Get the configured model name.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set the model name explicitly.
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        $this->lastUsedModel = $model;
        return $this;
    }

    /**
     * Get the model that successfully answered the last request.
     */
    public function getLastUsedModel(): string
    {
        return $this->lastUsedModel ?? $this->model;
    }

    /**
     * Generate a response using the AI model.
     * Used for general-purpose prompts (data analysis, questions, etc.)
     */
    public function generate(string $prompt_question, array $options = []): ?string
    {
        try {
            $defaultOptions = ['temperature' => 0.2, 'topP' => 0.95, 'maxOutputTokens' => 4096];
            $mergedOptions = array_merge($defaultOptions, $options);
            $raw = $this->callGemini($this->getSystemPrompt(), $prompt_question, $mergedOptions);
            return $this->cleanResponse($raw);
        } catch (\Throwable $e) {
            Log::error('GeminiService::generate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a SQL query from a natural language question.
     * Applies strict low-temperature instructions to return ONLY raw SQL.
     */
    public function generateSQL(string $prompt): ?string
    {
        try {
            $options = [
                'temperature' => 0.05,
                'topP' => 0.85,
                'maxOutputTokens' => 2048,
            ];
            $sql = $this->callGemini($this->getSQLSystemPrompt(), $prompt, $options);
            return $this->cleanSQL($sql);
        } catch (\Throwable $e) {
            Log::error('GeminiService::generateSQL error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a human-friendly answer from data.
     * Used for the second-pass (data -> visualization/cards/charts).
     */
    public function generateAnswer(string $prompt): ?string
    {
        try {
            $options = [
                'temperature' => 0.2,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ];
            $raw = $this->callGemini($this->getAnswerSystemPrompt(), $prompt, $options);
            return $this->cleanResponse($raw);
        } catch (\Throwable $e) {
            Log::error('GeminiService::generateAnswer error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Classify user intent as read/create/update/delete/confirm/cancel.
     * Returns raw JSON string from the AI.
     */
    public function classifyIntent(string $prompt): ?string
    {
        try {
            $options = [
                'temperature' => 0.1,
                'topP' => 0.8,
                'maxOutputTokens' => 4096,
            ];
            $raw = $this->callGemini($this->getIntentSystemPrompt(), $prompt, $options);
            return $this->cleanJSON($raw);
        } catch (\Throwable $e) {
            Log::error('GeminiService::classifyIntent error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Core method: call the Gemini API with automatic model failover.
     */
    private function callGemini(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        // Build candidate models list: primary model first, followed by fallbacks
        $candidateModels = array_unique(array_merge([$this->model], $this->fallbackModels));

        $generationConfig = [
            'temperature' => $options['temperature'] ?? 0.2,
            'topP' => $options['topP'] ?? 0.95,
            'maxOutputTokens' => $options['maxOutputTokens'] ?? 4096,
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => $generationConfig,
        ];

        $lastException = null;

        foreach ($candidateModels as $modelName) {
            try {
                $endpoint = "models/{$modelName}:generateContent?key=" . urlencode($this->geminiApiKey ?? '');
                $response = $this->client->post($endpoint, [
                    'json' => $payload,
                ]);

                if ($response->getStatusCode() === 200) {
                    $body = json_decode((string) $response->getBody(), true);
                    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (!empty($text)) {
                        $this->lastUsedModel = $modelName;
                        return $text;
                    }
                }
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("GeminiService call failed for model [{$modelName}]: " . $e->getMessage() . ". Retrying next candidate...");
                continue;
            }
        }

        if ($lastException) {
            Log::error("GeminiService: All candidate models failed. Last error: " . $lastException->getMessage());
        }

        return null;
    }

    /**
     * System prompt for the SQL generation role.
     */
    private function getSQLSystemPrompt(): string
    {
        return 'You are an elite MySQL 8.0 Senior Database Architect and Query Specialist for Jupiter ERP. '
            . 'Your mission is to generate ONLY a single, 100% syntactically correct, optimized MySQL read-only SELECT query that answers the user question exactly. '
            . 'MANDATORY RULES: '
            . '1. Output ONLY the raw SQL query. No preamble, no explanation, no markdown backticks, no commentary. '
            . '2. Use ONLY valid MySQL SELECT statements. Never use INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, or CREATE. '
            . '3. Respect exact table names and column names from the provided schema and ERP Knowledge Map. NEVER invent tables or columns. '
            . '4. Always include "WHERE deleted_at IS NULL" on tables with soft deletes. '
            . '5. For date aggregations, use YEAR(date) and MONTH(date). Use 2026 if year is not specified. '
            . '6. If you cannot answer or data is completely missing, return: SELECT 1 AS no_data';
    }

    /**
     * System prompt for the answer/analysis role.
     */
    private function getAnswerSystemPrompt(): string
    {
        return 'You are an elite Enterprise AI Data Analyst and UI Architect for the Jupiter ERP system. '
            . 'Always deliver certified, high-precision ERP enterprise intelligence. '
            . 'You transform ERP database results into stunning, highly professional, responsive HTML5 dashboards styled with Material Design Icons and interactive ApexCharts. '
            . 'MANDATORY RULES: '
            . '1. TRUTH & ACCURACY: Base every figure strictly on the provided database results. Never fabricate numbers or metrics. '
            . '2. LANGUAGE: Detect the exact language used by the user (English, French, Spanish, Arabic) and respond entirely in that same language. '
            . '3. ICONS: Every icon tag MUST include the "v-icon notranslate mdi mdi-" prefix, for example: <i class="v-icon notranslate mdi mdi-calendar"></i>. NEVER output class="mdi-calendar" alone. '
            . '4. NUMBER FORMATTING: Counts as integers with thousand space separators (e.g., 21 092); amounts in MAD with 2 decimals (e.g., 3 433 830.00 MAD); weights in KG with 2 decimals (e.g., 1 500.00 KG); percentages with 1 decimal (e.g., 15.4%). '
            . '5. STYLING & ARCHITECTURE: Use the exact provided HTML card structure (.ai-analytics-card, .ai-analytics-kpi, .ai-analytics-inner, .ai-analytics-table). Tables MUST use standard HTML <table>, <thead>, <tbody>, <tr>, <th>, <td> with right-aligned numeric cells. '
            . '6. CHARTS & GRAPHS: When user requests graphics/statistics, or when the data has monthly/category distributions, generate interactive ApexCharts using self-executing vanilla JS: (function(){ var options = {...}; var chart = new ApexCharts(document.querySelector("#chart_id"), options); chart.render(); })(). Always supply unique chart IDs. '
            . '7. OUTPUT: Return strictly the HTML container and script block. No markdown wrapper, no conversational preamble.';
    }

    /**
     * General system prompt (fallback).
     */
    private function getSystemPrompt(): string
    {
        return 'You are an AI assistant for the Jupiter ERP application. '
            . 'Answer questions accurately without hallucinations. '
            . 'Give clean, minimal responses. '
            . 'CRITICAL LANGUAGE RULE: Detect the exact language the user used in their message and ALWAYS answer in that EXACT SAME language.';
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
            . '"update" — user wants to modify, update, change records. '
            . '"delete" — user wants to delete, remove, or suppress records. KEY WORDS: delete, remove, suppress, supprimer. '
            . '"confirm" — user is saying yes, ok, confirm, go ahead, oui, d\'accord to a previous action proposal. '
            . '"cancel" — user is saying no, cancel, stop, annuler to a previous action proposal. '
            . 'IMPORTANT: There is NO record limit. If the user asks to create 20, 50, 100, or any number of records, you MUST generate ALL of them in the "records" array. Never truncate, summarize, or use ellipsis. Generate every single record. '
            . 'Return ONLY the JSON object. Nothing else.';
    }

    /**
     * Clean SQL output — strip markdown fences, comments, introductory text.
     */
    private function cleanSQL(?string $sql): ?string
    {
        if (!$sql) {
            return null;
        }

        $sql = trim($sql);

        // If inside code fence ```sql ... ```
        if (preg_match('/```(?:sql|mysql)?\s*([\s\S]*?)\s*```/i', $sql, $matches)) {
            $sql = trim($matches[1]);
        }

        // If preamble text exists before SELECT
        if (preg_match('/(SELECT\s+[\s\S]+)/i', $sql, $matches)) {
            $sql = trim($matches[1]);
        }

        // Remove trailing semicolons and trailing explanation after semicolon
        if (preg_match('/^(SELECT\s+[\s\S]+?);/i', $sql, $matches)) {
            $sql = trim($matches[1]);
        }

        $sql = rtrim(trim($sql), ';');

        return $sql ?: null;
    }

    /**
     * Clean general/HTML response — strip markdown wrappers if present.
     */
    private function cleanResponse(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        $text = trim($text);

        // If wrapped in ```html ... ``` or ```vue ... ```
        if (preg_match('/```(?:html|vue|xml)?\s*(<div[\s\S]*?<\/script>)\s*```/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // Strip code fences if at edges
        $text = preg_replace('/^```(?:html|vue|xml)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return trim($text) ?: null;
    }

    /**
     * Clean JSON output — strip markdown fences, extract JSON object.
     */
    private function cleanJSON(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

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
