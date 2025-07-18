<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Traits\SimilarityCalculationTrait;
use Illuminate\Support\Facades\Http;

class OpenAIService implements ChatServiceInterface, EmbeddingServiceInterface
{
    use SimilarityCalculationTrait;

    protected $apiKey;
    protected $model;
    protected $embeddingModel;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
        $this->embeddingModel = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->client = $this->client();
    }

    public function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30);
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

        $response = $this->client->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$response->successful()) {
            throw new \Exception('OpenAI chat request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI chat response format');
        }

        return $responseData['choices'][0]['message']['content'];
    }

    public function createEmbedding(string|array $text): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/embeddings", [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

            if ($response->successful()) {
                return collect($response->json()['data'])
                    ->sortBy('index')
                    ->pluck('embedding')
                    ->all();
            }

            throw new \Exception('Failed to create embedding: ' . $response->body());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}