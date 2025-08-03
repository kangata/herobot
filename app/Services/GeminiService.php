<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\SpeechToTextServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements ChatServiceInterface, EmbeddingServiceInterface, SpeechToTextServiceInterface
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
        $this->embeddingModel = config('services.gemini.embedding_model');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta";
        $this->client = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
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

        $response = $this->client->post("models/{$model}:generateContent", $payload);

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
            if (is_array($text)) {
                return $this->createBatchEmbeddings($text);
            }

            $payload = [
                'model' => "models/$this->embeddingModel",
                'content' => ['parts' => [['text' => $text]]],
                'output_dimensionality' => 768,
            ];

            $response = $this->client->post("models/{$this->embeddingModel}:embedContent", $payload);

            if (!$response->successful()) {
                Log::error('Gemini Embedding API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to create embedding: ' . $response->body());
            }

            $responseData = $response->json();
            return $responseData['embedding']['values'] ?? [];
        } catch (\Exception $e) {
            Log::error('Gemini Embedding Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create embeddings for multiple texts in a single batch request
     *
     * @param array $texts Array of text strings to embed
     * @return array Array of embedding vectors, indexed by input order
     */
    public function createBatchEmbeddings(array $texts): array
    {
        try {
            $requests = [];

            foreach ($texts as $text) {
                $requests[] = [
                    'model' => "models/$this->embeddingModel",
                    'content' => [
                        'parts' => [['text' => $text]]
                    ],
                    'output_dimensionality' => 768,
                ];
            }

            $payload = ['requests' => $requests];

            $response = $this->client->post("models/{$this->embeddingModel}:batchEmbedContents", $payload);

            if (!$response->successful()) {
                Log::error('Gemini Batch Embedding API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to create batch embeddings: ' . $response->body());
            }

            $responseData = $response->json();
            $embeddings = [];

            if (isset($responseData['embeddings'])) {
                foreach ($responseData['embeddings'] as $embedding) {
                    $embeddings[] = $embedding['values'] ?? [];
                }
            }

            return $embeddings;
        } catch (\Exception $e) {
            Log::error('Gemini Batch Embedding Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function transcribe(string $audioData, string $mimeType, ?string $language = null): string
    {
        try {
            // Remove data URL prefix if present
            $audioData = preg_replace('/^data:[a-zA-Z0-9\/\-\.]+;base64,/', '', $audioData);

            $contents = [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Please transcribe the audio content exactly as spoken. Return only the transcribed text without any additional commentary or formatting.'],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $audioData
                            ]
                        ]
                    ]
                ]
            ];

            $payload = [
                'contents' => $contents
            ];

            $response = $this->client->post("models/{$this->model}:generateContent", $payload);

            if (!$response->successful()) {
                Log::error('Gemini Transcription API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Gemini transcription request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid Gemini transcription response format');
            }

            return trim($responseData['candidates'][0]['content']['parts'][0]['text']);
        } catch (\Exception $e) {
            Log::error('Gemini Transcription Error', ['error' => $e->getMessage()]);
            throw new \Exception('Speech-to-text transcription failed: ' . $e->getMessage());
        }
    }
}
