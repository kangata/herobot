<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService implements ChatServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $baseUrl;
    protected $siteUrl;
    protected $siteName;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->model = config('services.openrouter.model');
        $this->baseUrl = 'https://openrouter.ai/api/v1';
        $this->siteUrl = config('app.url');
        $this->siteName = config('app.name');
    }

    public function generateResponse(array $messages, ?string $model = null): string
    {
        $model = $model ?? $this->model;

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => $this->siteUrl,
            'X-Title' => $this->siteName,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter request failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        Log::info('OpenRouter Request and Response:', [
            'model' => $model,
            'messages' => $messages,
            'response' => $responseData,
        ]);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenRouter response format');
        }

        return $responseData['choices'][0]['message']['content'];
    }
} 