<?php

namespace App\Services;

use GuzzleHttp\Client;

class GeminiService
{
    private $geminiApiKey;
    private $client;

    public function __construct()
    {
        $this->geminiApiKey = env('GEMINI_API_KEY'); // Set in .env file
        $this->client = new Client(['base_uri' => 'https://generativelanguage.googleapis.com/v1/']);
    }

    public function generate(string $prompt_question): string | null
    {
        $response = $this->client->post("models/gemini-2.5-pro:generateContent", [
            'query' => [
                'X-goog-api-key' => $this->geminiApiKey
            ],
            'json' => [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt_question]
                        ]
                    ]
                ]
            ]
        ]);
        $body = json_decode($response->getBody(), true);
        $rawQuery = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$rawQuery) {
            return null;
        }

        // Remove markdown ```sql and ```
        $answer = preg_replace('/```sql|```/', '', trim($rawQuery));
        return $answer;
    }
}
