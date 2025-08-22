<?php

namespace App\Services\Contracts;

interface EmbeddingServiceInterface
{
    public function createEmbedding(string|array $text): array;

    public function getProvider(): string;

    public function getEmbeddingModel(): string;
} 