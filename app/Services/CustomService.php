<?php

namespace App\Services;

use Cloudstudio\Ollama\Facades\Ollama;

class CustomService
{
    /**
     * The model to use for AI generation.
     */
    private string $model;

    /**
     * The thinking/reasoning level to use.
     * GPT-OSS & Qwen/DeepSeek support: low, medium, high.
     */
    private string $thinkingLevel = 'high';

    /**
     * Cached responsive Ollama URL.
     */
    private ?string $activeOllamaUrl = null;

    public function __construct()
    {
        $this->model = env('OLLAMA_MODEL', 'gpt-oss:120b-cloud');
    }

    /**
     * Set the thinking level.
     */
    public function setThinkingLevel(string $level): self
    {
        if (in_array(strtolower($level), ['low', 'medium', 'high', 'true', '1'])) {
            $this->thinkingLevel = $level;
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
        return $this;
    }

    /**
     * Probe and return an active Ollama instance URL.
     */
    public function getActiveOllamaUrl(): ?string
    {
        if ($this->activeOllamaUrl !== null) {
            return $this->activeOllamaUrl;
        }

        $candidates = array_unique(array_filter([
            config('ollama-laravel.url'),
            env('OLLAMA_URL'),
            'http://127.0.0.1:11434',
            'http://192.168.1.204:11435',
            'https://ollama.atlantickanfish.com',
        ]));

        foreach ($candidates as $url) {
            $url = rtrim($url, '/');
            $ch = curl_init($url . '/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($res && $httpCode < 400) {
                $this->activeOllamaUrl = $url;
                config(['ollama-laravel.url' => $url]);

                // Auto-detect model if configured model not found in available tags
                $data = json_decode($res, true);
                $availableModels = array_map(fn($m) => $m['name'] ?? '', $data['models'] ?? []);
                if (!empty($availableModels)) {
                    if (!in_array($this->model, $availableModels) && !env('OLLAMA_MODEL')) {
                        $this->model = $availableModels[0];
                    }
                }
                return $this->activeOllamaUrl;
            }
        }

        return null;
    }

    /**
     * Safely executes an Ollama query with optional thinking level, keepAlive, and options.
     */
    private function executeOllama(string $systemPrompt, string $userPrompt, array $options = []): mixed
    {
        try {
            $activeUrl = $this->getActiveOllamaUrl();
            if (!$activeUrl) {
                // Ollama is offline or unreachable - fail fast to enable instantaneous analytics engine
                return null;
            }

            $client = Ollama::agent($systemPrompt)
                ->prompt($userPrompt)
                ->model($this->model)
                ->keepAlive('15m');

            if (!empty($options)) {
                $client->options($options);
            }

            // Safely apply think if supported, ignoring if method is undefined or unsupported
            if (is_object($client) && method_exists($client, 'think')) {
                try {
                    $client->think($this->thinkingLevel);
                } catch (\Throwable $e) {
                    // Silently ignore think() errors
                }
            }

            return $client->ask();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a response using the AI model.
     * Used for general-purpose prompts (data analysis, questions, etc.)
     */
    public function generate(string $prompt_question, array $options = []): string|null
    {
        $defaultOptions = ['temperature' => 0.2, 'num_ctx' => 16384];
        $response = $this->executeOllama($this->getSystemPrompt(), $prompt_question, array_merge($defaultOptions, $options));

        try {
            return $this->cleanResponse($response['response'] ?? null);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a SQL query from a natural language question.
     * Applies strict low-temperature instructions to return ONLY raw SQL.
     */
    public function generateSQL(string $prompt): string|null
    {
        $options = [
            'temperature' => 0.05,
            'top_p' => 0.85,
            'num_ctx' => 16384,
        ];
        $response = $this->executeOllama($this->getSQLSystemPrompt(), $prompt, $options);

        try {
            $sql = $response['response'] ?? null;
            return $this->cleanSQL($sql);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a human-friendly answer from data.
     * Used for the second-pass (data → visualization).
     */
    public function generateAnswer(string $prompt): string|null
    {
        $options = [
            'temperature' => 0.2,
            'top_p' => 0.95,
            'num_ctx' => 32768,
        ];
        $response = $this->executeOllama($this->getAnswerSystemPrompt(), $prompt, $options);

        try {
            return $this->cleanResponse($response['response'] ?? null);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate a concise plain-text answer from data for regular/simple questions.
     * Returns direct, clear text without wrapping in complex HTML dashboards.
     */
    public function generatePlainTextAnswer(string $prompt): string|null
    {
        $options = [
            'temperature' => 0.15,
            'top_p' => 0.9,
            'num_ctx' => 16384,
        ];
        $response = $this->executeOllama($this->getPlainTextSystemPrompt(), $prompt, $options);

        try {
            $text = $response['response'] ?? null;
            if (!$text) return null;
            // Strip code fences if model returned them
            $text = preg_replace('/^```(?:markdown|text)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/', '', $text);
            return trim($text) ?: null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * System prompt for plain text answer role (regular questions).
     */
    private function getPlainTextSystemPrompt(): string
    {
        return 'You are an elite Enterprise AI Data Analyst for Jupiter ERP. '
            . 'Your goal is to answer the user question directly, accurately, and politely in plain text based STRICTLY on the provided database results. '
            . 'MANDATORY RULES: '
            . '1. TRUTH & ACCURACY: Base every figure strictly on the database results. Never invent or estimate numbers. '
            . '2. PLAIN TEXT ONLY: Do NOT output HTML cards, tables, div tags, or charts. Output clean, direct plain text with standard Markdown formatting (**bold** for numbers and key facts, bullet points if listing a few items). '
            . '3. ZERO DUPLICATES: Report distinct, deduplicated facts. If counting vouchers, state the exact distinct count and total amount/weight if available. '
            . '4. LANGUAGE: Detect the exact language used by the user (English, French, Spanish, Arabic) and respond entirely in that SAME language. '
            . '5. CURRENCY & UNITS: Amounts in MAD (e.g., 850 420.00 MAD); weights in KG (e.g., 42 150.00 KG); counts as integers (e.g., 24). '
            . '6. BE DIRECT: Give the precise answer directly in 1 to 3 concise, highly professional sentences.';
    }

    /**
     * Classify user intent as read/create/delete.
     * Returns raw JSON string from the AI.
     */
    public function classifyIntent(string $prompt): string|null
    {
        $options = [
            'temperature' => 0.1,
            'num_ctx' => 8192,
        ];
        $response = $this->executeOllama($this->getIntentSystemPrompt(), $prompt, $options);

        try {
            return $this->cleanJSON($response['response'] ?? null);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * System prompt for the SQL generation role.
     */
    private function getSQLSystemPrompt(): string
    {
        return 'You are an elite MySQL 8.0 Senior Database Architect and Query Specialist for Jupiter ERP. '
            . 'Your mission is to generate ONLY a single, 100% syntactically correct, optimized MySQL read-only SELECT query that answers the user question exactly. '
            . 'MANDATORY RULES: '
            . '1. Output ONLY the raw SQL query. No preamble, no explanation, no markdown ticks, no commentary. '
            . '2. Use ONLY valid MySQL SELECT statements. Never use INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, or CREATE. '
            . '3. Respect exact table names and column names from the provided schema and ERP Knowledge Map. NEVER invent tables or columns. '
            . '4. Always include "WHERE deleted_at IS NULL" on tables with soft deletes. '
            . '5. ANTI-DUPLICATION RULE: Always use COUNT(DISTINCT parent.id) and SELECT DISTINCT parent.id... when counting or listing records. NEVER join 1:N detail tables (such as detail_charges, detail_shippings, detail_productions) when calculating parent counts or sums, as doing so multiplies and duplicates rows! '
            . '6. CHARGE BON / VOUCHER: Each row in `charges` represents a charge voucher / bon (reception_tracking_number stores the bon number). To count charge vouchers for a given month/year: SELECT COUNT(DISTINCT id) FROM charges WHERE deleted_at IS NULL AND YEAR(date) = 2026 AND MONTH(date) = 11. '
            . '7. CONVERSATION CONTEXT: If conversation history is provided, understand the context and resolve follow-up references, pronouns (it, that, them, those), and prior constraints accurately. '
            . '8. For date aggregations, use YEAR(date) and MONTH(date). Use 2026 if year not specified. '
            . '9. If you cannot answer or data is completely missing, return: SELECT 1 AS no_data';
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
            . '5. STYLING & ARCHITECTURE: Use the exact provided HTML card structure (.ai-analytics-card, .ai-analytics-kpi, .ai-analytics-inner, .ai-analytics-table). Tables MUST use standard HTML <table>, <thead>, <tbody>, <tr>, <th>, <td> with right-aligned numeric cells. Charts MUST use the self-executing vanilla JS ApexCharts snippet (function(){ new ApexCharts(...) })() and never rely on external mounting. '
            . '6. OUTPUT: Return strictly the HTML container and script block. No markdown wrapper, no conversational preamble.';
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
