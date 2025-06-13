<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIChatService implements ChatServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
        $this->baseUrl = "https://api.openai.com/v1";
    }

    public function generateResponse(array $messages, ?string $model = null, ?string $media = null, ?string $mimeType = null): string
    {
        $model = $model ?? $this->model;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$response->successful()) {
            throw new \Exception('OpenAI chat request failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI chat response format');
        }

        return $responseData['choices'][0]['message']['content'];
    }
}