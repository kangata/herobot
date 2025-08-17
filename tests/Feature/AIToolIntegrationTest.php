<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Services\AIResponseService;
use App\Services\ToolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AIToolIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Bot $bot;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->bot = Bot::factory()->create(['team_id' => $this->team->id]);
        
        $this->tool = Tool::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Get Weather',
            'description' => 'Get current weather for a location',
            'type' => 'http',
            'params' => [
                'url' => 'https://api.openweathermap.org/data/2.5/weather',
                'method' => 'GET',
                'query' => [
                    'q' => '{{location}}',
                    'appid' => '{{api_key}}',
                    'units' => 'metric'
                ]
            ],
            'parameters_schema' => [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string'],
                    'api_key' => ['type' => 'string']
                ],
                'required' => ['location', 'api_key']
            ],
            'is_active' => true
        ]);
    }

    public function test_ai_response_service_includes_available_tools()
    {
        $toolService = new ToolService();
        $aiService = new AIResponseService($toolService);

        // Mock the AI service factory and embedding service
        $this->mockAIServices();

        // Mock HTTP response for weather API
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'main' => ['temp' => 25],
                'weather' => [['description' => 'clear sky']]
            ], 200)
        ]);

        $response = $aiService->generateResponse(
            $this->bot,
            "What's the weather in Jakarta?",
            collect([]),
            null,
            'whatsapp'
        );

        $this->assertIsString($response);
        $this->assertNotFalse($response);
    }

    public function test_ai_response_service_handles_tool_calls()
    {
        $toolService = new ToolService();
        $aiService = new AIResponseService($toolService);

        // Mock chat service to return tool calls
        $mockChatService = Mockery::mock('App\Services\Contracts\ChatServiceInterface');
        $mockChatService->shouldReceive('generateResponse')
            ->once()
            ->with(Mockery::type('array'), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::type('array'))
            ->andReturn([
                'content' => '',
                'tool_calls' => [[
                    'id' => 'call_123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'Get Weather',
                        'arguments' => json_encode(['location' => 'Jakarta', 'api_key' => 'test_key'])
                    ]
                ]]
            ]);
        
        // Mock second call for final response
        $mockChatService->shouldReceive('generateResponse')
            ->once()
            ->with(Mockery::type('array'), Mockery::any(), Mockery::any(), Mockery::any(), [])
            ->andReturn('The weather in Jakarta is 25°C with clear sky.');

        $mockEmbeddingService = Mockery::mock('App\Services\Contracts\EmbeddingServiceInterface');
        $mockEmbeddingService->shouldReceive('createEmbedding')
            ->andReturn([0.1, 0.2, 0.3]);

        // Mock the static methods of AIServiceFactory
        Mockery::mock('alias:App\Services\AIServiceFactory')
            ->shouldReceive('createChatService')
            ->andReturn($mockChatService)
            ->shouldReceive('createEmbeddingService')
            ->andReturn($mockEmbeddingService);

        // Mock HTTP response for weather API
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'main' => ['temp' => 25],
                'weather' => [['description' => 'clear sky']]
            ], 200)
        ]);

        $response = $aiService->generateResponse(
            $this->bot,
            "What's the weather in Jakarta?",
            collect([]),
            null,
            'whatsapp'
        );

        $this->assertIsString($response);
        $this->assertStringContainsString('25', $response);
        $this->assertStringContainsString('clear sky', $response);
    }

    public function test_tool_execution_creates_execution_record()
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'main' => ['temp' => 25],
                'weather' => [['description' => 'clear sky']]
            ], 200)
        ]);

        $toolService = new ToolService();
        $execution = $toolService->executeTool($this->tool, [
            'location' => 'Jakarta',
            'api_key' => 'test_key'
        ]);

        $this->assertEquals('completed', $execution->status);
        $this->assertNotNull($execution->output);
        $this->assertArrayHasKey('success', $execution->output);
        $this->assertTrue($execution->output['success']);
    }

    public function test_tool_execution_handles_api_failures()
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['error' => 'Invalid API key'], 401)
        ]);

        $toolService = new ToolService();
        $execution = $toolService->executeTool($this->tool, [
            'location' => 'Jakarta',
            'api_key' => 'invalid_key'
        ]);

        $this->assertEquals('completed', $execution->status);
        $this->assertNotNull($execution->output);
        $this->assertEquals(401, $execution->output['status']);
    }

    public function test_tool_execution_with_invalid_parameters()
    {
        $toolService = new ToolService();
        $execution = $toolService->executeTool($this->tool, [
            // Missing required parameters
        ]);

        $this->assertEquals('failed', $execution->status);
        $this->assertNotNull($execution->error);
        $this->assertStringContainsString('Required parameter', $execution->error);
    }

    public function test_ai_service_handles_tool_call_responses()
    {
        // Test that the AI service can get available tools for a bot
        $toolService = new ToolService();
        $tools = $toolService->getToolsForBot($this->bot->id);
        
        $this->assertCount(1, $tools);
        $this->assertEquals('function', $tools[0]['type']);
        $this->assertArrayHasKey('function', $tools[0]);
        $this->assertEquals('Get Weather', $tools[0]['function']['name']);
        $this->assertEquals('Get current weather for a location', $tools[0]['function']['description']);
        $this->assertArrayHasKey('parameters', $tools[0]['function']);
    }

    public function test_inactive_tools_are_not_available_to_ai()
    {
        $this->tool->update(['is_active' => false]);

        $toolService = new ToolService();
        $tools = $toolService->getToolsForBot($this->bot->id);

        $this->assertEmpty($tools);
    }

    public function test_tools_from_different_teams_are_not_available()
    {
        $otherTeam = Team::factory()->create();
        $otherBot = Bot::factory()->create(['team_id' => $otherTeam->id]);

        $toolService = new ToolService();
        $tools = $toolService->getToolsForBot($otherBot->id);

        $this->assertEmpty($tools);
    }

    private function mockAIServices()
    {
        $mockChatService = Mockery::mock('App\Services\Contracts\ChatServiceInterface');
        $mockChatService->shouldReceive('generateResponse')
            ->with(Mockery::type('array'), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::type('array'))
            ->andReturn('The weather is nice today.');

        $mockEmbeddingService = Mockery::mock('App\Services\Contracts\EmbeddingServiceInterface');
        $mockEmbeddingService->shouldReceive('createEmbedding')
            ->andReturn([0.1, 0.2, 0.3]);

        // Mock the static methods of AIServiceFactory
        Mockery::mock('alias:App\Services\AIServiceFactory')
            ->shouldReceive('createChatService')
            ->andReturn($mockChatService)
            ->shouldReceive('createEmbeddingService')
            ->andReturn($mockEmbeddingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
