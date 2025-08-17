<?php

namespace Tests\Unit\Services;

use App\Models\Tool;
use App\Models\ToolExecution;
use App\Services\ToolService;
use App\Services\Tools\HttpTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolServiceTest extends TestCase
{
    use RefreshDatabase;

    private ToolService $toolService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->toolService = new ToolService();
    }

    public function test_create_tool_instance_for_http_tool()
    {
        $tool = Tool::factory()->create([
            'type' => 'http',
            'name' => 'Test HTTP Tool',
            'description' => 'Test Description',
            'parameters_schema' => ['type' => 'object']
        ]);

        $instance = $this->toolService->createToolInstance($tool);

        $this->assertInstanceOf(HttpTool::class, $instance);
        $this->assertEquals('Test HTTP Tool', $instance->getName());
        $this->assertEquals('Test Description', $instance->getDescription());
    }

    public function test_create_tool_instance_throws_exception_for_unknown_type()
    {
        $tool = Tool::factory()->create(['type' => 'unknown']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown tool type: unknown');

        $this->toolService->createToolInstance($tool);
    }

    public function test_get_available_tool_types()
    {
        $types = $this->toolService->getAvailableToolTypes();

        $this->assertIsArray($types);
        $this->assertCount(1, $types);
        
        $httpType = collect($types)->firstWhere('type', 'http');
        $this->assertNotNull($httpType);
        $this->assertEquals('http', $httpType['type']);
        $this->assertArrayHasKey('name', $httpType);
        $this->assertArrayHasKey('description', $httpType);
        $this->assertArrayHasKey('class', $httpType);
    }

    public function test_execute_tool_creates_execution_record()
    {
        $tool = Tool::factory()->create([
            'type' => 'http',
            'params' => [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET'
            ]
        ]);

        $parameters = ['test' => 'value'];

        $execution = $this->toolService->executeTool($tool, $parameters);

        $this->assertInstanceOf(ToolExecution::class, $execution);
        $this->assertEquals($tool->id, $execution->tool_id);
        $this->assertEquals($parameters, $execution->input_parameters);
        $this->assertContains($execution->status, ['completed', 'failed']);
    }

    public function test_execute_tool_handles_exceptions()
    {
        $tool = Tool::factory()->create([
            'type' => 'http',
            'params' => [
                'url' => 'invalid-url',
                'method' => 'GET'
            ]
        ]);

        $execution = $this->toolService->executeTool($tool, []);

        $this->assertEquals('failed', $execution->status);
        $this->assertNotNull($execution->error);
    }

    public function test_validate_tool_configuration_for_http_tool()
    {
        $params = [
            'url' => 'https://api.example.com',
            'method' => 'GET'
        ];
        $schema = ['type' => 'object', 'properties' => []];

        $errors = $this->toolService->validateToolConfiguration('http', $params, $schema);

        $this->assertEmpty($errors);
    }

    public function test_validate_tool_configuration_returns_errors_for_missing_url()
    {
        $params = ['method' => 'GET'];
        $schema = ['type' => 'object', 'properties' => []];

        $errors = $this->toolService->validateToolConfiguration('http', $params, $schema);

        $this->assertContains('HTTP tool requires a URL parameter', $errors);
    }

    public function test_validate_tool_configuration_returns_errors_for_missing_method()
    {
        $params = ['url' => 'https://api.example.com'];
        $schema = ['type' => 'object', 'properties' => []];

        $errors = $this->toolService->validateToolConfiguration('http', $params, $schema);

        $this->assertContains('HTTP tool requires a method parameter', $errors);
    }

    public function test_validate_tool_configuration_returns_errors_for_invalid_schema()
    {
        $params = ['url' => 'https://api.example.com', 'method' => 'GET'];
        $schema = ['type' => 'string']; // Invalid schema type

        $errors = $this->toolService->validateToolConfiguration('http', $params, $schema);

        $this->assertContains('Parameters schema must be an object type', $errors);
    }

    public function test_test_tool_returns_success_for_valid_tool()
    {
        $tool = Tool::factory()->create([
            'type' => 'http',
            'params' => [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET'
            ]
        ]);

        $result = $this->toolService->testTool($tool, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_test_tool_returns_error_for_invalid_tool()
    {
        $tool = Tool::factory()->create([
            'type' => 'http',
            'params' => [
                'url' => 'invalid-url',
                'method' => 'GET'
            ]
        ]);

        $result = $this->toolService->testTool($tool, []);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
