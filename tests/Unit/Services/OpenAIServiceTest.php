<?php

namespace Tests\Unit\Services;

use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock config values
        config([
            'services.openai.api_key' => 'test-api-key',
            'services.openai.model' => 'gpt-4',
            'services.openai.embedding_model' => 'text-embedding-3-small',
            'services.openai.base_url' => 'https://api.openai.com/v1'
        ]);
    }

    public function test_generate_response_returns_string_for_simple_text()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Hello, how can I help you today?'
                    ]
                ]]
            ], 200)
        ]);

        $openAIService = new OpenAIService();
        
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello']
        ];

        $response = $openAIService->generateResponse($messages);

        $this->assertIsArray($response);
        $this->assertEquals('Hello, how can I help you today?', $response['content']);
    }

    public function test_generate_response_returns_array_for_tool_calls()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'I need to check the weather for you.',
                        'tool_calls' => [[
                            'id' => 'call_123',
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_weather',
                                'arguments' => '{"location": "Jakarta"}'
                            ]
                        ]]
                    ]
                ]]
            ], 200)
        ]);

        $openAIService = new OpenAIService();

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

        $response = $openAIService->generateResponse($messages, null, null, null, $tools);

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
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'index' => 0,
                        'embedding' => [0.1, 0.2, 0.3, 0.4]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'total_tokens' => 10
                ]
            ], 200)
        ]);

        $openAIService = new OpenAIService();
        
        $text = 'Test text for embedding';
        $embedding = $openAIService->createEmbedding($text);

        $this->assertIsArray($embedding);
        $this->assertEquals([
            'embeddings' => [[0.1, 0.2, 0.3, 0.4]],
            'token_usage' => [
                'input_tokens' => 10,
                'output_tokens' => 0,
                'total_tokens' => 10,
            ],
        ], $embedding);
    }

    public function test_transcribe_audio()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'text' => 'Hello world'
            ], 200)
        ]);

        $openAIService = new OpenAIService();
        
        $audioData = base64_encode('fake-audio-data');
        $mimeType = 'audio/mp3';

        $transcription = $openAIService->transcribe($audioData, $mimeType);

        $this->assertEquals('Hello world', $transcription);
    }
}
