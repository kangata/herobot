<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    use HasFactory;

    const DEFAULT_PROMPT = 'You are a helpful AI assistant. You aim to provide accurate, helpful, and concise responses while being friendly and professional.';

    protected $fillable = [
        'team_id', 
        'name', 
        'description', 
        'prompt', 
        'ai_chat_service', 
        'ai_embedding_service', 
        'ai_speech_to_text_service',
        'openai_api_key',
        'gemini_api_key'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function channels()
    {
        return $this->morphedByMany(Channel::class, 'connectable', 'bot_connections');
    }

    public function knowledge()
    {
        return $this->morphedByMany(Knowledge::class, 'connectable', 'bot_connections');
    }

    public function tools()
    {
        return $this->morphedByMany(Tool::class, 'connectable', 'bot_connections');
    }

    /**
     * Get the AI chat service configuration (provider/model)
     */
    public function getAiChatService(): string
    {
        return $this->ai_chat_service ?? config('services.ai.chat_service', 'gemini') . '/' . config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Get the AI embedding service configuration (provider/model)
     */
    public function getAiEmbeddingService(): string
    {
        return $this->ai_embedding_service ?? config('services.ai.embedding_service', 'gemini') . '/' . config('services.gemini.embedding_model', 'text-embedding-004');
    }

    /**
     * Get the AI speech-to-text service configuration (provider/model)
     */
    public function getAiSpeechToTextService(): string
    {
        return $this->ai_speech_to_text_service ?? config('services.ai.speech_to_text_service', 'gemini') . '/' . config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Parse service configuration to get provider and model
     */
    public function parseServiceConfig(string $serviceConfig): array
    {
        $parts = explode('/', $serviceConfig);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid service configuration format. Expected 'provider/model', got: {$serviceConfig}");
        }
        
        return [
            'provider' => $parts[0],
            'model' => $parts[1]
        ];
    }

    /**
     * Get custom API key for a provider
     */
    public function getCustomApiKey(string $provider): ?string
    {
        return match($provider) {
            'openai' => $this->openai_api_key,
            'gemini' => $this->gemini_api_key,
            default => null,
        };
    }

    /**
     * Get available AI providers
     */
    public static function getAvailableProviders(): array
    {
        return [
            'openai' => 'OpenAI',
            'gemini' => 'Google Gemini',
        ];
    }

    /**
     * Get available models for a provider
     */
    public static function getAvailableModels(string $provider): array
    {
        return match($provider) {
            'openai' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            'gemini' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
            default => [],
        };
    }

    /**
     * Get available embedding models for a provider
     */
    public static function getAvailableEmbeddingModels(string $provider): array
    {
        return match($provider) {
            'openai' => [
                'text-embedding-3-large' => 'Text Embedding 3 Large',
                'text-embedding-3-small' => 'Text Embedding 3 Small',
                'text-embedding-ada-002' => 'Text Embedding Ada 002',
            ],
            'gemini' => [
                'text-embedding-004' => 'Text Embedding 004',
            ],
            default => [],
        };
    }

    /**
     * Get available service configurations for a service type
     */
    public static function getAvailableServiceConfigs(string $serviceType): array
    {
        $configs = [];
        
        foreach (self::getAvailableProviders() as $provider => $providerName) {
            if ($serviceType === 'embedding') {
                $models = self::getAvailableEmbeddingModels($provider);
            } else {
                $models = self::getAvailableModels($provider);
            }
            
            foreach ($models as $model => $modelName) {
                $configs["{$provider}/{$model}"] = "{$providerName} - {$modelName}";
            }
        }
        
        return $configs;
    }
}
