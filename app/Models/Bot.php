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
        if ($this->ai_chat_service) {
            return $this->ai_chat_service;
        }
        
        $provider = config('services.ai.chat_service', 'gemini');
        $model = $provider === 'openai' 
            ? config('services.openai.model')
            : config('services.gemini.model');
            
        return $provider . '/' . $model;
    }

    /**
     * Get the AI embedding service configuration (provider/model)
     */
    public function getAiEmbeddingService(): string
    {
        if ($this->ai_embedding_service) {
            return $this->ai_embedding_service;
        }
        
        $provider = config('services.ai.embedding_service', 'gemini');
        $model = $provider === 'openai' 
            ? config('services.openai.embedding_model')
            : config('services.gemini.embedding_model');
            
        return $provider . '/' . $model;
    }

    /**
     * Get the AI speech-to-text service configuration (provider/model)
     */
    public function getAiSpeechToTextService(): string
    {
        if ($this->ai_speech_to_text_service) {
            return $this->ai_speech_to_text_service;
        }
        
        $provider = config('services.ai.speech_to_text_service', 'gemini');
        $model = $provider === 'openai' 
            ? 'whisper-1'
            : config('services.gemini.model');
            
        return $provider . '/' . $model;
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
     * Check if bot is using custom API keys for any service
     */
    public function isUsingCustomApiKeys(): bool
    {
        // Check if any of the configured services use custom API keys
        $chatConfig = $this->parseServiceConfig($this->getAiChatService());
        $embeddingConfig = $this->parseServiceConfig($this->getAiEmbeddingService());
        $speechConfig = $this->parseServiceConfig($this->getAiSpeechToTextService());
        
        return $this->getCustomApiKey($chatConfig['provider']) !== null ||
               $this->getCustomApiKey($embeddingConfig['provider']) !== null ||
               $this->getCustomApiKey($speechConfig['provider']) !== null;
    }
}
