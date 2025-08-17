<?php

namespace Tests\Unit\Services\Tools;

use App\Services\Tools\HttpTool;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpToolTest extends TestCase
{
    public function test_http_tool_initialization()
    {
        $config = [
            'name' => 'Test HTTP Tool',
            'description' => 'Test Description',
            'parameters_schema' => [
                'type' => 'object',
                'properties' => [
                    'url' => ['type' => 'string']
                ]
            ]
        ];

        $tool = new HttpTool($config);

        $this->assertEquals('Test HTTP Tool', $tool->getName());
        $this->assertEquals('Test Description', $tool->getDescription());
        $this->assertIsArray($tool->getParametersSchema());
    }

    public function test_http_tool_execute_get_request()
    {
        Http::fake([
            'httpbin.org/get' => Http::response(['success' => true], 200)
        ]);

        $config = [
            'name' => 'GET Test',
            'params' => [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET'
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);
        $result = $tool->execute([]);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertEquals(['success' => true], $result['body']);
    }

    public function test_http_tool_execute_post_request_with_body()
    {
        Http::fake([
            'httpbin.org/post' => Http::response(['received' => true], 201)
        ]);

        $config = [
            'name' => 'POST Test',
            'params' => [
                'url' => 'https://httpbin.org/post',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => [
                    'name' => '{{name}}',
                    'email' => '{{email}}'
                ]
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);
        $result = $tool->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(201, $result['status']);
        $this->assertEquals(['received' => true], $result['body']);
    }

    public function test_http_tool_execute_with_query_parameters()
    {
        Http::fake([
            'httpbin.org/get*' => Http::response(['query_received' => true], 200)
        ]);

        $config = [
            'name' => 'Query Test',
            'params' => [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET',
                'query' => [
                    'page' => '{{page}}',
                    'limit' => '{{limit}}'
                ]
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);
        $result = $tool->execute([
            'page' => '1',
            'limit' => '10'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_http_tool_execute_with_headers()
    {
        Http::fake([
            'httpbin.org/get' => Http::response(['headers_received' => true], 200)
        ]);

        $config = [
            'name' => 'Headers Test',
            'params' => [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET',
                'headers' => [
                    'Authorization' => 'Bearer {{token}}',
                    'X-Custom-Header' => 'custom-value'
                ]
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);
        $result = $tool->execute(['token' => 'abc123']);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_http_tool_execute_handles_http_errors()
    {
        Http::fake([
            'httpbin.org/status/500' => Http::response(['error' => 'Server Error'], 500)
        ]);

        $config = [
            'name' => 'Error Test',
            'params' => [
                'url' => 'https://httpbin.org/status/500',
                'method' => 'GET'
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);
        $result = $tool->execute([]);

        $this->assertTrue($result['success']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals(['error' => 'Server Error'], $result['body']);
    }

    public function test_http_tool_execute_throws_exception_for_invalid_url()
    {
        $config = [
            'name' => 'Invalid URL Test',
            'params' => [
                'url' => 'invalid-url',
                'method' => 'GET'
            ],
            'parameters_schema' => ['type' => 'object']
        ];

        $tool = new HttpTool($config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to execute HTTP request');

        $tool->execute([]);
    }

    public function test_http_tool_validates_required_parameters()
    {
        $config = [
            'name' => 'Validation Test',
            'params' => [
                'url' => 'https://httpbin.org/get/{{id}}',
                'method' => 'GET'
            ],
            'parameters_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string']
                ],
                'required' => ['id']
            ]
        ];

        $tool = new HttpTool($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Required parameter 'id' is missing");

        $tool->execute([]);
    }
}
