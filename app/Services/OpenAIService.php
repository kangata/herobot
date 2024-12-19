<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-3.5-turbo');
        
        // Check if the C extension is available
        if (!function_exists('fast_cosine_similarity')) {
            Log::warning('Vector search C extension not available. Using PHP implementation.');
        }
    }

    public function splitTextIntoChunks($text, $maxChunkSize = 800)
    {
        // Split text into paragraphs (sections separated by blank lines)
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            
            // Skip empty paragraphs
            if (empty($paragraph)) {
                continue;
            }
            
            // Extract the first line as title (if it exists)
            $lines = explode("\n", $paragraph);
            $title = trim($lines[0]);
            $content = $paragraph;
            
            // If the paragraph is longer than maxChunkSize, split it into smaller chunks
            if (strlen($content) > $maxChunkSize) {
                $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
                $currentChunk = '';
                
                foreach ($sentences as $sentence) {
                    if (strlen($currentChunk) + strlen($sentence) <= $maxChunkSize) {
                        $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                    } else {
                        if ($currentChunk) {
                            $chunks[] = [
                                'title' => $title,
                                'content' => trim($currentChunk)
                            ];
                        }
                        $currentChunk = $sentence;
                    }
                }
                
                if ($currentChunk) {
                    $chunks[] = [
                        'title' => $title,
                        'content' => trim($currentChunk)
                    ];
                }
            } else {
                $chunks[] = [
                    'title' => $title,
                    'content' => $content
                ];
            }
        }
        
        return $chunks;
    }

    public function createEmbedding($text)
    {
        // If text is an array, use batch processing
        if (is_array($text)) {
            return $this->createBatchEmbeddings($text);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
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

    public function createBatchEmbeddings(array $texts)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $texts
            ]);

            if ($response->successful()) {
                // Return array of embeddings in the same order as input
                return collect($response->json()['data'])
                    ->sortBy('index')
                    ->pluck('embedding')
                    ->all();
            }

            throw new \Exception('Failed to create batch embeddings: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error creating batch embeddings: ' . $e->getMessage());
            throw $e;
        }
    }

    public function searchSimilarKnowledge($query, $bot, $limit = 3)
    {
        try {
            // Create embedding for the query
            $queryEmbedding = $this->createEmbedding($query);

            // Get only necessary vectors with optimized query
            $knowledgeVectors = $bot->knowledge()
                ->where('status', 'completed')
                ->with(['vectors:id,knowledge_id,text,vector']) // Select only needed fields
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

    private function calculateSimilarity($vector1, $vector2)
    {
        if (function_exists('fast_cosine_similarity')) {
            return fast_cosine_similarity($vector1, $vector2);
        }
        
        // Fallback to PHP implementation
        return $this->cosineSimilarity($vector1, $vector2);
    }

    public function cosineSimilarity($vector1, $vector2)
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