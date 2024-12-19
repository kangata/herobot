<?php

namespace App\Services;

use App\Events\KnowledgeUpdated;
use App\Models\Knowledge;
use Illuminate\Support\Facades\Log;

class KnowledgeService
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function indexKnowledge(Knowledge $knowledge)
    {
        try {
            // Update status to indexing
            $knowledge->update(['status' => 'indexing']);

            // Split the text into chunks
            $chunks = $this->openAIService->splitTextIntoChunks($knowledge->text);

            // Delete existing vectors
            $knowledge->vectors()->delete();

            // Extract all texts for batch processing
            $texts = array_column($chunks, 'content');
            
            // Create embeddings for all chunks at once
            $vectors = $this->openAIService->createEmbedding($texts);

            // Create vector records for each chunk with its corresponding embedding
            foreach ($chunks as $index => $chunk) {
                $knowledge->vectors()->create([
                    'text' => $chunk['content'],
                    'vector' => $vectors[$index]
                ]);
            }

            // Update status to completed
            $knowledge->update(['status' => 'completed']);

            KnowledgeUpdated::dispatch($knowledge);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index knowledge: ' . $e->getMessage());
            $knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }
} 