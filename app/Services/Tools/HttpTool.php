<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpTool extends BaseTool
{
    protected function initialize(): void
    {
        $this->name = $this->config['name'] ?? 'HTTP Request';

        $this->description = $this->config['description'] ?? 'Make HTTP requests to external APIs';
        
        $this->parametersSchema = $this->config['parameters_schema'] ?? [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The URL to make the request to',
                ],
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'default' => 'GET',
                ],
                'headers' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
                'body' => [
                    'type' => ['object', 'string', 'null'],
                ],
                'query' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $parameters): array
    {
        $this->validateParameters($parameters);
        
        $replacer = new ParameterReplacer();
        $config = $replacer->replace($this->config['params'], $parameters);
        
        $url = $config['url'];
        $method = strtolower($config['method'] ?? 'get');
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? null;
        $query = $config['query'] ?? [];

        try {
            $request = Http::withHeaders($headers)->timeout(30);
            
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }
            
            $response = $request->$method($url, $body);

            return [
                'success' => true,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->json() ?? $response->body(),
                'execution_time' => microtime(true),
            ];
        } catch (\Exception $e) {
            Log::error('HTTP Tool Error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to execute HTTP request: ' . $e->getMessage());
        }
    }
}
