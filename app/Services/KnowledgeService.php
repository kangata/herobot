<?php

namespace App\Services;

use App\Events\KnowledgeUpdated;
use App\Models\Knowledge;
use App\Services\AIServiceFactory;
use Illuminate\Support\Facades\Log;

class KnowledgeService
{
    protected $embeddingService;

    public function __construct()
    {
        $this->embeddingService = AIServiceFactory::createEmbeddingService();
    }

    public function indexKnowledge(Knowledge $knowledge)
    {
        try {
            $knowledge->update(['status' => 'indexing']);

            // Extract and chunk content
            $content = $knowledge->text;
            $chunks = $this->splitTextIntoChunks($content);
            $texts = array_column($chunks, 'content');

            // Create embeddings using configured service
            $embed = $this->embeddingService->createEmbedding($texts);

            $vectors = $embed['embeddings'];

            // Delete existing vectors
            $knowledge->vectors()->delete();

            // Create vector records
            foreach ($chunks as $index => $chunk) {
                $knowledge->vectors()->create([
                    'text' => $chunk['content'],
                    'vector' => $vectors[$index],
                ]);
            }

            $knowledge->update(['status' => 'completed']);

            KnowledgeUpdated::dispatch($knowledge);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index knowledge: ' . $e->getMessage());
            $knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Split text into chunks for processing.
     * This is a generic implementation that works for all embedding services.
     */
    private function splitTextIntoChunks($text, $maxChunkSize = 800)
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
                                'content' => trim($currentChunk),
                            ];
                        }
                        $currentChunk = $sentence;
                    }
                }

                if ($currentChunk) {
                    $chunks[] = [
                        'title' => $title,
                        'content' => trim($currentChunk),
                    ];
                }
            } else {
                $chunks[] = [
                    'title' => $title,
                    'content' => $content,
                ];
            }
        }

        return $chunks;
    }
}
