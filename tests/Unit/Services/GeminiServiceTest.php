<?php

namespace Tests\Unit\Services;

use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock config values
        config([
            'services.gemini.api_key' => 'test-api-key',
            'services.gemini.model' => 'gemini-1.5-flash',
            'services.gemini.embedding_model' => 'text-embedding-004'
        ]);
    }

    public function test_generate_response_returns_string_for_simple_text()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'Hello, how can I help you?']]
                    ]
                ]]
            ], 200)
        ]);

        $geminiService = new GeminiService();

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello']
        ];

        $response = $geminiService->generateResponse($messages);

        $this->assertIsString($response);
        $this->assertEquals('Hello, how can I help you?', $response);
    }

    public function test_generate_response_returns_array_for_tool_calls()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'I need to check the weather for you.'],
                            [
                                'functionCall' => [
                                    'name' => 'get_weather',
                                    'args' => ['location' => 'Jakarta']
                                ]
                            ]
                        ]
                    ]
                ]]
            ], 200)
        ]);

        $geminiService = new GeminiService();

        $messages = [
            ['role' => 'user', 'content' => 'What is the weather in Jakarta?']
        ];

        $tools = [[
            'type' => 'function',
            'function' => [
                'name' => 'get_weather',
                'description' => 'Get weather for a location',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string']
                    ]
                ]
            ]
        ]];

        $response = $geminiService->generateResponse($messages, null, null, null, $tools);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('content', $response);
        $this->assertArrayHasKey('tool_calls', $response);
        $this->assertEquals('I need to check the weather for you.', $response['content']);
        $this->assertCount(1, $response['tool_calls']);
        $this->assertEquals('get_weather', $response['tool_calls'][0]['function']['name']);
    }

    public function test_create_embedding_returns_vector()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => [
                    'values' => [0.1, 0.2, 0.3, 0.4]
                ]
            ], 200)
        ]);

        $geminiService = new GeminiService();

        $text = 'Test text for embedding';
        $embedding = $geminiService->createEmbedding($text);

        $this->assertIsArray($embedding);
        $this->assertEquals([0.1, 0.2, 0.3, 0.4], $embedding);
    }

    public function test_transcribe_audio()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'Hello world']]
                    ]
                ]]
            ], 200)
        ]);

        $geminiService = new GeminiService();

        $audioData = base64_encode('fake-audio-data');
        $mimeType = 'audio/mp3';

        $transcription = $geminiService->transcribe($audioData, $mimeType);

        $this->assertEquals('Hello world', $transcription);
    }
}
