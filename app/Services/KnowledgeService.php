<?php

namespace App\Services;

use App\Events\KnowledgeUpdated;
use App\Models\Knowledge;
use App\Services\AIServiceFactory;
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
            $knowledge->update(['status' => 'indexing']);
            
            // Extract and chunk content
            $content = $knowledge->text;
            $chunks = $this->openAIService->splitTextIntoChunks($content);
            $texts = array_column($chunks, 'content');
            
            // Use configured embedding service
            $embeddingService = AIServiceFactory::createEmbeddingService();
            $vectors = $embeddingService->createEmbedding($texts);
            
            Log::info('Creating embeddings', [
                'service' => get_class($embeddingService),
                'knowledge_id' => $knowledge->id,
                'chunk_count' => count($chunks),
            ]);
            
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
}
