<?php

namespace App\Services;

use App\Models\Bot;
use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\SpeechToTextServiceInterface;
use InvalidArgumentException;

class AIServiceFactory
{
    public static function createChatService(?Bot $bot = null): ChatServiceInterface
    {
        $serviceConfig = $bot ? $bot->getAiChatService() : self::getDefaultChatService();
        $config = $bot ? $bot->parseServiceConfig($serviceConfig) : self::parseServiceConfig($serviceConfig);
        
        return match($config['provider']) {
            'openai' => self::createOpenAIService($config['model'], $bot),
            'gemini' => self::createGeminiService($config['model'], $bot),
            default => throw new InvalidArgumentException("Unsupported chat service provider: {$config['provider']}")
        };
    }
    
    public static function createEmbeddingService(?Bot $bot = null): EmbeddingServiceInterface
    {
        $serviceConfig = $bot ? $bot->getAiEmbeddingService() : self::getDefaultEmbeddingService();
        $config = $bot ? $bot->parseServiceConfig($serviceConfig) : self::parseServiceConfig($serviceConfig);

        return match($config['provider']) {
            'openai' => self::createOpenAIService($config['model'], $bot, 'embedding'),
            'gemini' => self::createGeminiService($config['model'], $bot, 'embedding'),
            default => throw new InvalidArgumentException("Unsupported embedding service provider: {$config['provider']}")
        };
    }

    public static function createSpeechToTextService(?Bot $bot = null): SpeechToTextServiceInterface
    {
        $serviceConfig = $bot ? $bot->getAiSpeechToTextService() : self::getDefaultSpeechToTextService();
        $config = $bot ? $bot->parseServiceConfig($serviceConfig) : self::parseServiceConfig($serviceConfig);

        return match($config['provider']) {
            'openai' => self::createOpenAIService($config['model'], $bot, 'speech'),
            'gemini' => self::createGeminiService($config['model'], $bot, 'speech'),
            default => throw new InvalidArgumentException("Unsupported speech-to-text service provider: {$config['provider']}")
        };
    }

    /**
     * Get default chat service configuration
     */
    private static function getDefaultChatService(): string
    {
        $provider = config('services.ai.chat_service', 'gemini');
        $model = config("services.{$provider}.model", $provider === 'openai' ? 'gpt-4o-mini' : 'gemini-2.5-flash');
        return "{$provider}/{$model}";
    }

    /**
     * Get default embedding service configuration
     */
    private static function getDefaultEmbeddingService(): string
    {
        $provider = config('services.ai.embedding_service', 'gemini');
        $model = config("services.{$provider}.embedding_model", $provider === 'openai' ? 'text-embedding-3-small' : 'text-embedding-004');
        return "{$provider}/{$model}";
    }

    /**
     * Get default speech-to-text service configuration
     */
    private static function getDefaultSpeechToTextService(): string
    {
        $provider = config('services.ai.speech_to_text_service', 'gemini');
        $model = config("services.{$provider}.model", $provider === 'openai' ? 'gpt-4o-mini' : 'gemini-2.5-flash');
        return "{$provider}/{$model}";
    }

    /**
     * Parse service configuration string
     */
    private static function parseServiceConfig(string $serviceConfig): array
    {
        $parts = explode('/', $serviceConfig);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid service configuration format. Expected 'provider/model', got: {$serviceConfig}");
        }
        
        return [
            'provider' => $parts[0],
            'model' => $parts[1]
        ];
    }
    
    private static function createOpenAIService(string $model, ?Bot $bot = null, string $serviceType = 'chat'): OpenAIService
    {
        // Use custom API key if available, otherwise use global config
        $apiKey = $bot?->getCustomApiKey('openai') ?? config('services.openai.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenAI API key not configured');
        }
        
        $service = app(OpenAIService::class);
        
        // Override API key if custom one is provided
        if ($bot?->getCustomApiKey('openai')) {
            $service->setApiKey($apiKey);
        }
        
        if ($serviceType === 'embedding') {
            $service->setEmbeddingModel($model);
        } else {
            $service->setModel($model);
        }
        
        return $service;
    }
    
    private static function createGeminiService(string $model, ?Bot $bot = null, string $serviceType = 'chat'): GeminiService
    {
        // Use custom API key if available, otherwise use global config
        $apiKey = $bot?->getCustomApiKey('gemini') ?? config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new InvalidArgumentException('Gemini API key not configured');
        }
        
        $service = app(GeminiService::class);
        
        // Override API key if custom one is provided
        if ($bot?->getCustomApiKey('gemini')) {
            $service->setApiKey($apiKey);
        }
        
        if ($serviceType === 'embedding') {
            $service->setEmbeddingModel($model);
        } else {
            $service->setModel($model);
        }
        
        return $service;
    }
}