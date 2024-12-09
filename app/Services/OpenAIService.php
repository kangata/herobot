<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAIService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-3.5-turbo');
    }

    public function splitTextIntoChunks($text, $maxChunkSize = 500)
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk) + strlen($sentence) <= $maxChunkSize) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            } else {
                if ($currentChunk) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $sentence;
            }
        }

        if ($currentChunk) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    public function createEmbedding($text)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-ada-002',
                'input' => $text
            ]);

            if ($response->successful()) {
                return $response->json()['data'][0]['embedding'];
            }

            throw new \Exception('Failed to create embedding: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error creating embedding: ' . $e->getMessage());
            throw $e;
        }
    }

    public function searchSimilarKnowledge($query, $bot, $limit = 3)
    {
        try {
            // Create embedding for the query
            $queryEmbedding = $this->createEmbedding($query);

            // Get all completed knowledge vectors for the bot
            $knowledgeVectors = $bot->knowledge()
                ->where('status', 'completed')
                ->with('vectors')
                ->get()
                ->flatMap(function ($knowledge) {
                    return $knowledge->vectors->map(function ($vector) use ($knowledge) {
                        return [
                            'knowledge_id' => $knowledge->id,
                            'knowledge_name' => $knowledge->name,
                            'text' => $vector->text,
                            'vector' => $vector->vector
                        ];
                    });
                });

            // Calculate cosine similarity for each vector
            $similarities = $knowledgeVectors->map(function ($item) use ($queryEmbedding) {
                $similarity = $this->cosineSimilarity($queryEmbedding, $item['vector']);
                return array_merge($item, ['similarity' => $similarity]);
            });

            // Sort by similarity and get top results
            $topResults = $similarities->sortByDesc('similarity')
                ->take($limit)
                ->values();

            return $topResults;
        } catch (\Exception $e) {
            Log::error('Error searching similar knowledge: ' . $e->getMessage());
            return collect();
        }
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

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        return $dotProduct / ($norm1 * $norm2);
    }
} 