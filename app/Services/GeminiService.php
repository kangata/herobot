<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements ChatServiceInterface, EmbeddingServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $embeddingModel;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->embeddingModel = config('services.gemini.embedding_model', 'gemini-embedding-exp-03-07');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";
        $this->client = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withQueryParameters([
                'key' => $this->apiKey,
            ]);
    }

    public function generateResponse(array $messages, ?string $model = null, ?string $media = null, ?string $mimeType = null): string
    {
        $model = $model ?? $this->model;

        $contents = [];
        $systemPrompt = '';

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt = $message['content'];
            } elseif (in_array($message['role'], ['user', 'assistant'])) {
                $role = $message['role'] === 'assistant' ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $message['content']]]
                ];
            }
        }

        if ($media) {
            $detectedMimeType = '';
            Log::info('GeminiService: Detected media', [
                'media_length' => strlen($media),
                'mime_type' => $mimeType,
            ]);
            if ($mimeType) {
                if (stripos($mimeType, 'audio') !== false) {
                    $detectedMimeType = 'audio/mp3';
                } else if (stripos($mimeType, 'image') !== false) {
                    $detectedMimeType = 'image/jpeg';
                }
            }
            $media = preg_replace('/^data:[a-zA-Z0-9\/\-\.]+;base64,/', '', $media);
            $lastIndex = count($contents) - 1;
            if ($lastIndex >= 0) {
                $contents[$lastIndex]['parts'][] = [
                    'inline_data' => [
                        'mime_type' => $detectedMimeType,
                        'data' => $media
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $contents
        ];

        // Only add system instruction if it exists and no image is provided
        if (!empty($systemPrompt) && !$media) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemPrompt]]
            ];
        }

        $response = $this->client->post("{$model}:generateContent", $payload);

        if (!$response->successful()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Gemini chat request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid Gemini chat response format');
        }

        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }

    public function createEmbedding(string|array $text): array
    {
        try {
            $payload = [
                'model' => $this->embeddingModel,
                'content' => [
                    'parts' => is_array($text) ? array_map(function ($t) {
                        return ['text' => $t];
                    }, $text) : [['text' => $text]]
                ]
            ];

            $response = $this->client->post("{$this->embeddingModel}:embedContent", $payload);

            if ($response->successful()) {
                return $response->json()['embedding']['values'] ?? [];
            }

            throw new \Exception('Failed to create embedding: ' . $response->body());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
