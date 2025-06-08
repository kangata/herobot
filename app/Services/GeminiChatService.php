<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatService implements ChatServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";
    }

    public function generateResponse(array $messages, ?string $model = null): string
    {
        $model = $model ?? $this->model;
        
        // Extract system prompt and user prompt from messages
        $systemPrompt = '';
        $userPrompt = '';
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt = $message['content'];
            } elseif ($message['role'] === 'user') {
                $userPrompt = $message['content']; // Get the last user message
            }
        }

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [
                ['parts' => [['text' => $userPrompt]]]
            ]
        ];

        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception('Gemini chat request failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid Gemini chat response format');
        }

        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
} 