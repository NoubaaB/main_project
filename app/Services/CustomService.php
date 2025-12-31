<?php

namespace App\Services;

use Cloudstudio\Ollama\Facades\Ollama;


class CustomService
{
    public function generate(string $prompt_question): string | null
    {
        $response = Ollama::agent('You are an AI assistant connected to a Laravel app database. answer the question without any hallucinations or explaining give just clean code')
            ->prompt($prompt_question)
            // ->model('gemini-3-pro-preview:latest')
            ->model('gemini-3-flash-preview:cloud')
            // ->model('qwen3-vl:235b-cloud')
            ->ask();
        try {
            return  $response['response'];
        } catch (\Exception $e) {
            return dd($response, $e);
        }
    }
}
