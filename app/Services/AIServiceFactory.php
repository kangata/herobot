<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use InvalidArgumentException;

class AIServiceFactory
{
    public static function createChatService(): ChatServiceInterface
    {
        $service = config('services.ai.chat_service');
        
        return match($service) {
            'openrouter' => self::createOpenRouterService(),
            'openai' => self::createOpenAIChatService(),
            'gemini' => self::createGeminiChatService(),
            default => throw new InvalidArgumentException("Unsupported chat service: {$service}")
        };
    }
    
    public static function createEmbeddingService(): EmbeddingServiceInterface  
    {
        $service = config('services.ai.embedding_service');
        
        return match($service) {
            'openai' => self::createOpenAIEmbeddingService(),
            'gemini' => self::createGeminiEmbeddingService(),
            default => throw new InvalidArgumentException("Unsupported embedding service: {$service}")
        };
    }
    
    private static function createOpenRouterService(): OpenRouterService
    {
        $apiKey = config('services.openrouter.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenRouter API key not configured');
        }
        return app(OpenRouterService::class);
    }
    
    private static function createOpenAIChatService(): OpenAIChatService
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenAI API key not configured');
        }
        return app(OpenAIChatService::class);
    }
    
    private static function createGeminiChatService(): GeminiChatService
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('Gemini API key not configured');
        }
        return app(GeminiChatService::class);
    }
    
    private static function createOpenAIEmbeddingService(): OpenAIEmbeddingService
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenAI API key not configured');
        }
        return app(OpenAIEmbeddingService::class);
    }
    
    private static function createGeminiEmbeddingService(): GeminiEmbeddingService
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('Gemini API key not configured');
        }
        return app(GeminiEmbeddingService::class);
    }
} 