<?php

namespace App\Services\Contracts;

interface ChatServiceInterface
{
    public function generateResponse(array $messages, ?string $model = null): string;
} 