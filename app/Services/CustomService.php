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
        $this->model = env('OLLAMA_MODEL', 'gemini-3-flash-preview:cloud');
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
}
