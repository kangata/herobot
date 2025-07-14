<?php

namespace App\Services;

use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Traits\SimilarityCalculationTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEmbeddingService implements EmbeddingServiceInterface
{
    use SimilarityCalculationTrait;

    protected $apiKey;
    protected $model;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.embedding_model', 'gemini-embedding-exp-03-07');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";

        if (!function_exists('fast_cosine_similarity')) {
            Log::warning('Vector search C extension not available. Using PHP implementation.');
        }
    }

    /**
     * Create embedding for single text or batch of texts.
     * If $text is array, will use batch processing.
     *
     * @param string|array $text
     * @return array Array embedding (numeric values) or array of embeddings for batch
     * @throws \Exception
     */
    public function createEmbedding($text): array
    {
        if (is_array($text)) {
            return $this->createBatchEmbeddings($text);
        }

        try {
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

            if ($response->successful()) {
                return $response->json()['embedding']['values'] ?? [];
            }

            throw new \Exception('Failed to create embedding: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error createEmbedding Gemini: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create batch embeddings from array of texts using optimized single API call.
     *
     * @param array $texts
     * @return array Array of embeddings (array of numeric arrays)
     * @throws \Exception
     */
    private function createBatchEmbeddings(array $texts): array
    {
        try {
            $parts = array_map(function ($text) {
                return ['text' => $text];
            }, $texts);

            $payload = [
                'model' => $this->model,
                'content' => [
                    'parts' => $parts
                ]
            ];

            $url = "{$this->baseUrl}/{$this->model}:embedContent?key={$this->apiKey}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $allEmbeddings = $response->json()['embedding']['values'] ?? null;

                if (!is_array($allEmbeddings)) {
                    throw new \Exception('Invalid batch embedding response format');
                }

                return $allEmbeddings;
            }

            throw new \Exception('Failed to create batch embeddings: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error createBatchEmbeddings Gemini: ' . $e->getMessage());
            throw $e;
        }
    }

    public function searchSimilarKnowledge($query, $bot, int $limit = 3)
    {
        try {
            // Create embedding for the query
            $queryEmbedding = $this->createEmbedding($query);

            // Get only necessary vectors with optimized query
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

            // Sort and limit results
            return $knowledgeVectors->sortByDesc('similarity')
                ->take($limit)
                ->values();

        } catch (\Exception $e) {
            Log::error('Error searching similar knowledge: ' . $e->getMessage());
            return collect();
        }
    }

}