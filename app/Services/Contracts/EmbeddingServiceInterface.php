<?php

namespace App\Services\Contracts;

interface EmbeddingServiceInterface
{
    public function createEmbedding(string|array $text): array;
} 