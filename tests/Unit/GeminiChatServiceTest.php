<?php

namespace Tests\Unit;

use App\Services\GeminiChatService;
use App\Services\Contracts\ChatServiceInterface;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiChatServiceTest extends TestCase
{
    private GeminiChatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set configuration values
        config([
            'services.gemini.api_key' => 'test-api-key',
            'services.gemini.model' => 'gemini-pro'
        ]);
        
        $this->service = new GeminiChatService();
    }

    public function test_implements_chat_service_interface()
    {
        $this->assertInstanceOf(ChatServiceInterface::class, $this->service);
    }

    public function test_generate_response_successful()
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello, how are you?']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello! I am doing well, thank you for asking.']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages);

        $this->assertEquals('Hello! I am doing well, thank you for asking.', $result);

        Http::assertSent(function ($request) {
            $this->assertEquals('POST', $request->method());
            $this->assertStringContainsString('gemini-pro:generateContent', $request->url());
            $this->assertStringContainsString('key=test-api-key', $request->url());
            
            $body = $request->data();
            $this->assertEquals('You are a helpful assistant', $body['system_instruction']['parts'][0]['text']);
            $this->assertEquals('user', $body['contents'][0]['role']);
            $this->assertEquals('Hello, how are you?', $body['contents'][0]['parts'][0]['text']);
            
            return true;
        });
    }

    public function test_generate_response_with_custom_model()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages, 'gemini-pro-vision');

        $this->assertEquals('Test response', $result);

        Http::assertSent(function ($request) {
            $this->assertStringContainsString('gemini-pro-vision:generateContent', $request->url());
            return true;
        });
    }

    public function test_generate_response_with_only_user_message()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Just a user message']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Response to user message']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages);

        $this->assertEquals('Response to user message', $result);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertArrayNotHasKey('system_instruction', $body);
            $this->assertEquals('user', $body['contents'][0]['role']);
            $this->assertEquals('Just a user message', $body['contents'][0]['parts'][0]['text']);
            return true;
        });
    }

    public function test_generate_response_with_multiple_user_messages()
    {
        $messages = [
            ['role' => 'system', 'content' => 'System prompt'],
            ['role' => 'user', 'content' => 'First message'],
            ['role' => 'user', 'content' => 'Second message']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Response to conversation']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages);

        $this->assertEquals('Response to conversation', $result);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertEquals('System prompt', $body['system_instruction']['parts'][0]['text']);
            // Should include both user messages in order
            $this->assertCount(2, $body['contents']);
            $this->assertEquals('user', $body['contents'][0]['role']);
            $this->assertEquals('First message', $body['contents'][0]['parts'][0]['text']);
            $this->assertEquals('user', $body['contents'][1]['role']);
            $this->assertEquals('Second message', $body['contents'][1]['parts'][0]['text']);
            return true;
        });
    }

    public function test_generate_response_with_conversation_history()
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Great to meet you. What would you like to know?'],
            ['role' => 'user', 'content' => 'I have two dogs in my house. How many paws are in my house?']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Each dog has 4 paws, so with 2 dogs you have 8 paws total in your house.']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages);

        $this->assertEquals('Each dog has 4 paws, so with 2 dogs you have 8 paws total in your house.', $result);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertEquals('You are a helpful assistant', $body['system_instruction']['parts'][0]['text']);
            
            // Should include all conversation messages in order
            $this->assertCount(3, $body['contents']);
            
            // First user message
            $this->assertEquals('user', $body['contents'][0]['role']);
            $this->assertEquals('Hello', $body['contents'][0]['parts'][0]['text']);
            
            // Assistant response (converted to 'model')
            $this->assertEquals('model', $body['contents'][1]['role']);
            $this->assertEquals('Great to meet you. What would you like to know?', $body['contents'][1]['parts'][0]['text']);
            
            // Second user message
            $this->assertEquals('user', $body['contents'][2]['role']);
            $this->assertEquals('I have two dogs in my house. How many paws are in my house?', $body['contents'][2]['parts'][0]['text']);
            
            return true;
        });
    }

    public function test_generate_response_http_request_fails()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Error'], 400)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gemini chat request failed:');

        $this->service->generateResponse($messages);
    }

    public function test_generate_response_invalid_response_format_missing_candidates()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $invalidResponse = [
            'some_other_field' => 'value'
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($invalidResponse, 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Gemini chat response format');

        $this->service->generateResponse($messages);
    }

    public function test_generate_response_invalid_response_format_missing_content()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $invalidResponse = [
            'candidates' => [
                [
                    'some_field' => 'value'
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($invalidResponse, 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Gemini chat response format');

        $this->service->generateResponse($messages);
    }

    public function test_generate_response_invalid_response_format_missing_parts()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $invalidResponse = [
            'candidates' => [
                [
                    'content' => [
                        'some_field' => 'value'
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($invalidResponse, 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Gemini chat response format');

        $this->service->generateResponse($messages);
    }

    public function test_generate_response_invalid_response_format_missing_text()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $invalidResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['some_field' => 'value']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($invalidResponse, 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Gemini chat response format');

        $this->service->generateResponse($messages);
    }

    public function test_generate_response_with_empty_messages()
    {
        $messages = [];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Response to empty input']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $result = $this->service->generateResponse($messages);

        $this->assertEquals('Response to empty input', $result);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertArrayNotHasKey('system_instruction', $body);
            $this->assertEmpty($body['contents']);
            return true;
        });
    }

    public function test_api_url_construction()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $this->service->generateResponse($messages, 'custom-model');

        Http::assertSent(function ($request) {
            $expectedUrl = 'https://generativelanguage.googleapis.com/v1beta/models/custom-model:generateContent?key=test-api-key';
            $this->assertEquals($expectedUrl, $request->url());
            return true;
        });
    }

    public function test_request_headers()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message']
        ];

        $expectedResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response']
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($expectedResponse, 200)
        ]);

        $this->service->generateResponse($messages);

        Http::assertSent(function ($request) {
            $this->assertEquals('application/json', $request->header('Content-Type')[0]);
            return true;
        });
    }
} 