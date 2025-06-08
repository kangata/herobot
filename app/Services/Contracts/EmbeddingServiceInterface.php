<?php

namespace App\Services\Contracts;

interface EmbeddingServiceInterface
{
    public function createEmbedding($text): array;
    public function searchSimilarKnowledge($query, $bot, int $limit = 3);
} 