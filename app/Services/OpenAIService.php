<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\SpeechToTextServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService implements ChatServiceInterface, EmbeddingServiceInterface, SpeechToTextServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $embeddingModel;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
        $this->embeddingModel = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->client = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Get the configured provider name
     */
    public function getProvider(): string
    {
        return 'OpenAI';
    }

    /**
     * Get the configured chat model name
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the configured embedding model name
     */
    public function getEmbeddingModel(): string
    {
        return $this->embeddingModel;
    }

    /**
     * Set the chat model
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Set the embedding model
     */
    public function setEmbeddingModel(string $embeddingModel): void
    {
        $this->embeddingModel = $embeddingModel;
    }

    /**
     * Set the API key
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
        $this->client = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    public function generateResponse(array $messages, ?string $model = null, ?string $media = null, ?string $mimeType = null, array $tools = []): array
    {
        $model = $model ?? $this->model;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 1,
            'max_completion_tokens' => 1000,
        ];

        // Add tools if provided
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client->post("chat/completions", $payload);

        if (!$response->successful()) {
            $body = $response->body();
            Log::info('OpenAI API', [
                'status' => $response->status(),
                'request' => $payload,
                'response' => $body
            ]);
            throw new \Exception('OpenAI chat request failed: ' . $body);
        }

        $responseData = $response->json();

        Log::info('OpenAI API', [
            'status' => $response->status(),
            'request' => $payload,
            'response' => $responseData
        ]);

        $message = $responseData['choices'][0]['message'] ?? null;
        if (!$message) {
            throw new \Exception('Invalid OpenAI chat response format: no message');
        }

        // Extract token usage data
        $usage = $responseData['usage'] ?? [];
        $tokenUsage = [
            'input_tokens' => $usage['prompt_tokens'] ?? 0,
            'output_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
        ];

        // Check for tool calls
        if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
            return [
                'content' => $message['content'] ?? '',
                'tool_calls' => $message['tool_calls'],
                'token_usage' => $tokenUsage
            ];
        }

        // Return content if available
        if (isset($message['content'])) {
            return [
                'content' => $message['content'],
                'token_usage' => $tokenUsage
            ];
        }

        throw new \Exception('Invalid OpenAI chat response format: no content or tool calls');
    }

    public function createEmbedding(string|array $text): array
    {
        try {
            $response = $this->client->post("embeddings", [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('OpenAI Embedding API', [
                    'status' => $response->status(),
                    'request' => [
                        'model' => $this->embeddingModel,
                        'input' => $text,
                    ],
                    'response' => $responseData
                ]);

                $embeddings = collect($responseData['data'])
                    ->sortBy('index')
                    ->pluck('embedding')
                    ->all();
                
                // Return embeddings with token usage
                return [
                    'embeddings' => $embeddings,
                    'token_usage' => [
                        'input_tokens' => $responseData['usage']['prompt_tokens'] ?? 0,
                        'output_tokens' => 0, // Embeddings don't have output tokens
                        'total_tokens' => $responseData['usage']['total_tokens'] ?? 0,
                    ]
                ];
            }

            throw new \Exception('Failed to create embedding: ' . $response->body());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function transcribe(string $audioData, string $mimeType, ?string $language = null): string
    {
        try {
            // Remove data URL prefix if present
            $audioData = preg_replace('/^data:[a-zA-Z0-9\/\-\.]+;base64,/', '', $audioData);

            // Decode base64 audio data
            $decodedAudio = base64_decode($audioData);

            if ($decodedAudio === false) {
                throw new \Exception('Invalid base64 audio data');
            }

            // Determine file extension from MIME type
            $extension = match($mimeType) {
                'audio/mp3', 'audio/mpeg' => 'mp3',
                'audio/wav' => 'wav',
                'audio/ogg' => 'ogg',
                'audio/m4a' => 'm4a',
                'audio/webm' => 'webm',
                default => 'mp3' // Default fallback
            };

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.' . $extension;
            file_put_contents($tempFile, $decodedAudio);

            try {
                // Prepare multipart form data
                $payload = [
                    'model' => 'whisper-1',
                    'file' => new \CURLFile($tempFile, $mimeType, 'audio.' . $extension),
                ];

                if ($language) {
                    $payload['language'] = $language;
                }

                // Create a new HTTP client for multipart request
                $response = Http::baseUrl($this->baseUrl)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ])
                    ->timeout(60) // Longer timeout for audio processing
                    ->attach('file', $decodedAudio, 'audio.' . $extension)
                    ->post('audio/transcriptions', [
                        'model' => 'whisper-1',
                        'language' => $language,
                    ]);

                if (!$response->successful()) {
                    throw new \Exception('OpenAI transcription request failed: ' . $response->body());
                }

                $responseData = $response->json();

                if (!isset($responseData['text'])) {
                    throw new \Exception('Invalid OpenAI transcription response format');
                }

                return $responseData['text'];
            } finally {
                // Clean up temporary file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Speech-to-text transcription failed: ' . $e->getMessage());
        }
    }
}
