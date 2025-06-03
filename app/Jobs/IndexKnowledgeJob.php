<?php

namespace App\Jobs;

use App\Models\Knowledge;
use App\Services\KnowledgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexKnowledgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $knowledge;

    public function __construct(Knowledge $knowledge)
    {
        $this->knowledge = $knowledge;
    }

    public function handle(KnowledgeService $knowledgeService)
    {
        try {
            $knowledgeService->indexKnowledge($this->knowledge);
        } catch (\Exception $e) {
            Log::error('Failed to index knowledge in job: '.$e->getMessage());
            $this->knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }
}
