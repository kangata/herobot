<?php

namespace App\Services;

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

            // Create embeddings for each chunk and store them
            foreach ($chunks as $chunk) {
                $vector = $this->openAIService->createEmbedding($chunk);
                
                $knowledge->vectors()->create([
                    'text' => $chunk,
                    'vector' => $vector
                ]);
            }

            // Update status to completed
            $knowledge->update(['status' => 'completed']);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index knowledge: ' . $e->getMessage());
            $knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }
} 