<?php

namespace App\Services;

use Cloudstudio\Ollama\Facades\Ollama;


class CustomService
{
    public function generate(string $prompt_question): string | null
    {
        $response = Ollama::agent('You are an AI assistant connected to a Laravel app database. answer the question without any hallucinations or explaining give just clean code')
            ->prompt($prompt_question)
            ->model('qwen3-coder:480b-cloud')
            ->ask();
        try {
            return  $response['response'];
        } catch (\Exception $e) {
            return dd($response, $e);
        }
    }
}
