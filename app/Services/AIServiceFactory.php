<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\SpeechToTextServiceInterface;
use InvalidArgumentException;

class AIServiceFactory
{
    public static function createChatService(): ChatServiceInterface
    {
        $service = config('services.ai.chat_service');
        
        return match($service) {
            'openai' => self::createOpenAIService(),
            'gemini' => self::createGeminiService(),
            default => throw new InvalidArgumentException("Unsupported chat service: {$service}")
        };
    }
    
    public static function createEmbeddingService(): EmbeddingServiceInterface
    {
        $service = config('services.ai.embedding_service');

        return match($service) {
            'openai' => self::createOpenAIService(),
            'gemini' => self::createGeminiService(),
            default => throw new InvalidArgumentException("Unsupported embedding service: {$service}")
        };
    }

    public static function createSpeechToTextService(): SpeechToTextServiceInterface
    {
        $service = config('services.ai.speech_to_text_service');

        return match($service) {
            'openai' => self::createOpenAIService(),
            'gemini' => self::createGeminiService(),
            default => throw new InvalidArgumentException("Unsupported speech-to-text service: {$service}")
        };
    }
    
    private static function createOpenAIService(): OpenAIService
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenAI API key not configured');
        }
        return app(OpenAIService::class);
    }
    
    private static function createGeminiService(): GeminiService
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('Gemini API key not configured');
        }
        return app(GeminiService::class);
    }
}