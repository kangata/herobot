<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\SpeechToTextServiceInterface;
use Illuminate\Support\Facades\Http;

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

    public function generateResponse(array $messages, ?string $model = null, ?string $media = null, ?string $mimeType = null, array $tools = []): array|string
    {
        $model = $model ?? $this->model;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];

        // Add tools if provided
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client->post("chat/completions", $payload);

        if (!$response->successful()) {
            throw new \Exception('OpenAI chat request failed: ' . $response->body());
        }

        $responseData = $response->json();

        $message = $responseData['choices'][0]['message'] ?? null;
        if (!$message) {
            throw new \Exception('Invalid OpenAI chat response format: no message');
        }

        // Check for tool calls
        if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
            return [
                'content' => $message['content'] ?? '',
                'tool_calls' => $message['tool_calls']
            ];
        }

        // Return content if available
        if (isset($message['content'])) {
            return $message['content'];
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
                return collect($response->json()['data'])
                    ->sortBy('index')
                    ->pluck('embedding')
                    ->all();
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
