<?php

namespace App\Services\Contracts;

interface ChatServiceInterface
{
    public function generateResponse(array $messages, ?string $model = null, ?string $media = null, ?string $mimeType = null, array $tools = []): array|string;

    public function getProvider(): string;

    public function getModel(): string;
} 