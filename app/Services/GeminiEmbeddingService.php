<?php

namespace App\Services;

use App\Services\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEmbeddingService implements EmbeddingServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.embedding_model');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";
    }

    public function createEmbedding($text): array
    {
        if (is_array($text)) {
            return $this->createBatchEmbeddings($text);
        }

        $payload = [
            'model' => $this->model,
            'content' => [
                'parts' => [['text' => $text]]
            ]
        ];

        $url = "{$this->baseUrl}/{$this->model}:embedContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception('Gemini embedding request failed: ' . $response->body());
        }

        return $response->json()['embedding']['values'] ?? [];
    }

    private function createBatchEmbeddings(array $texts): array
    {
        $embeddings = [];
        foreach ($texts as $text) {
            $embeddings[] = $this->createEmbedding($text);
        }
        return $embeddings;
    }

    public function searchSimilarKnowledge($query, $bot, int $limit = 3)
    {
        try {
            $queryEmbedding = $this->createEmbedding($query);
            
            $knowledgeVectors = $bot->knowledge()
                ->where('status', 'completed')
                ->with(['vectors:id,knowledge_id,text,vector'])
                ->get()
                ->flatMap(function ($knowledge) use ($queryEmbedding) {
                    return $knowledge->vectors->map(function ($vector) use ($queryEmbedding) {
                        return [
                            'text' => $vector->text,
                            'similarity' => $this->calculateSimilarity($queryEmbedding, $vector->vector),
                        ];
                    });
                });

            return $knowledgeVectors->sortByDesc('similarity')
                ->take($limit)
                ->values();

        } catch (\Exception $e) {
            Log::error('Error searching similar knowledge: ' . $e->getMessage());
            return collect();
        }
    }

    private function calculateSimilarity($vector1, $vector2)
    {
        if (function_exists('fast_cosine_similarity')) {
            return fast_cosine_similarity($vector1, $vector2);
        }

        return $this->cosineSimilarity($vector1, $vector2);
    }

    private function cosineSimilarity($vector1, $vector2)
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($vector1 as $i => $value) {
            $dotProduct += $value * $vector2[$i];
            $norm1 += $value * $value;
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }
} 