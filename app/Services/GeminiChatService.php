<?php

namespace App\Services;

use App\Services\Contracts\ChatServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatService implements ChatServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";
    }

    public function generateResponse(array $messages, ?string $model = null, ?string $media = null): string
    {
        $model = $model ?? $this->model;
        
        $systemPrompt = '';
        $contents = [];
        
        if ($media) {
            $media = preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $media);
            $contents[] = [
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => 'image/jpeg',
                            'data' => $media
                        ]
                    ],
                    [
                        'text' => $messages[count($messages) - 1]['content']
                    ]
                ]
            ];
        } else {
            foreach ($messages as $message) {
                if ($message['role'] === 'system') {
                    $systemPrompt = $message['content'];
                } elseif (in_array($message['role'], ['user', 'assistant'])) {
                    // Convert 'assistant' role to 'model' for Gemini API
                    $role = $message['role'] === 'assistant' ? 'model' : 'user';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $message['content']]]
                    ];
                }
            }
        }

        $payload = [
            'contents' => $contents
        ];

        // Only add system instruction if it exists and tidak ada image
        if (!empty($systemPrompt) && !$media) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemPrompt]]
            ];
        }

        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

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
} 