<?php

// Config for Cloudstudio/Ollama

return [
    'model' => env('OLLAMA_MODEL', 'gemini-3-flash-preview:cloud'),
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'default_prompt' => env('OLLAMA_DEFAULT_PROMPT', 'You are a helpful AI assistant for a fish management ERP system.'),
    'connection' => [
        'timeout' => env('OLLAMA_CONNECTION_TIMEOUT', 300),
    ],
    'headers' => [
        'Authorization' => 'Bearer ' . env('OLLAMA_API_KEY'),
    ],
];
