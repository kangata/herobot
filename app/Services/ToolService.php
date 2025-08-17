<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\ToolExecution;
use App\Services\Tools\BaseTool;
use App\Services\Tools\HttpTool;
use Illuminate\Support\Facades\Log;

class ToolService
{
    protected array $toolClasses = [
        'http' => HttpTool::class,
    ];

    public function createToolInstance(Tool $tool): BaseTool
    {
        $toolClass = $this->toolClasses[$tool->type] ?? null;
        
        if (!$toolClass) {
            throw new \InvalidArgumentException("Unknown tool type: {$tool->type}");
        }

        $config = array_merge($tool->toArray(), [
            'name' => $tool->name,
            'description' => $tool->description,
            'parameters_schema' => $tool->parameters_schema,
        ]);

        return new $toolClass($config);
    }

    public function getAvailableToolTypes(): array
    {
        $types = [];
        
        foreach ($this->toolClasses as $type => $class) {
            $instance = new $class([
                'name' => ucfirst($type) . ' Tool',
                'description' => 'Default ' . $type . ' tool',
                'parameters_schema' => [],
            ]);
            
            $types[] = [
                'type' => $type,
                'name' => $instance->getName(),
                'description' => $instance->getDescription(),
                'class' => $class,
            ];
        }

        return $types;
    }

    public function executeTool(Tool $tool, array $parameters, ?int $chatHistoryId = null): ToolExecution
    {
        $execution = ToolExecution::create([
            'tool_id' => $tool->id,
            'chat_history_id' => $chatHistoryId,
            'status' => 'in_progress',
            'input_parameters' => $parameters,
        ]);

        try {
            $toolInstance = $this->createToolInstance($tool);
            $output = $toolInstance->execute($parameters);
            
            $execution->update([
                'status' => 'completed',
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            Log::error('Tool execution failed', [
                'tool_id' => $tool->id,
                'error' => $e->getMessage(),
                'parameters' => $parameters,
            ]);
            
            $execution->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }

        return $execution->fresh();
    }

    public function validateToolConfiguration(string $type, array $params, array $parametersSchema): array
    {
        $errors = [];
        
        if (!isset($this->toolClasses[$type])) {
            $errors[] = "Unknown tool type: {$type}";
            return $errors;
        }

        // Validate required fields based on tool type
        switch ($type) {
            case 'http':
                if (!isset($params['url'])) {
                    $errors[] = 'HTTP tool requires a URL parameter';
                }
                if (!isset($params['method'])) {
                    $errors[] = 'HTTP tool requires a method parameter';
                }
                break;
                
            case 'database':
                if (!isset($params['query'])) {
                    $errors[] = 'Database tool requires a query parameter';
                }
                break;
        }

        // Validate parameters schema structure
        if (!isset($parametersSchema['type']) || $parametersSchema['type'] !== 'object') {
            $errors[] = 'Parameters schema must be an object type';
        }

        if (!isset($parametersSchema['properties'])) {
            $errors[] = 'Parameters schema must have properties defined';
        }

        return $errors;
    }

    public function testTool(Tool $tool, array $testParameters): array
    {
        $startTime = microtime(true);
        
        try {
            $toolInstance = $this->createToolInstance($tool);
            $result = $toolInstance->execute($testParameters);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2); // Duration in milliseconds
            
            return [
                'success' => true,
                'duration' => $duration,
                'result' => $result,
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2); // Duration in milliseconds
            
            return [
                'success' => false,
                'duration' => $duration,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getToolsForBot(int $botId): array
    {
        // Get tools associated with the bot's team
        $bot = \App\Models\Bot::with('team')->findOrFail($botId);
        
        return Tool::where('team_id', $bot->team_id)
            ->where('is_active', true)
            ->get()
            ->map(function ($tool) {
                return [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool->name,
                        'description' => $tool->description,
                        'parameters' => $tool->parameters_schema,
                    ],
                ];
            })
            ->toArray();
    }
}
