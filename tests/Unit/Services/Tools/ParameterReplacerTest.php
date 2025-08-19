<?php

namespace Tests\Unit\Services\Tools;

use App\Services\Tools\ParameterReplacer;
use Tests\TestCase;

class ParameterReplacerTest extends TestCase
{
    private ParameterReplacer $replacer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->replacer = new ParameterReplacer();
    }

    public function test_replace_simple_parameters()
    {
        $template = [
            'url' => 'https://api.example.com/users/{{user_id}}',
            'method' => 'GET'
        ];
        $parameters = ['user_id' => '123'];

        $result = $this->replacer->replace($template, $parameters);

        $this->assertEquals('https://api.example.com/users/123', $result['url']);
        $this->assertEquals('GET', $result['method']);
    }

    public function test_replace_multiple_parameters()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}',
            'headers' => [
                'Authorization' => 'Bearer {{token}}',
                'Content-Type' => 'application/json'
            ],
            'body' => [
                'name' => '{{name}}',
                'email' => '{{email}}'
            ]
        ];
        $parameters = [
            'endpoint' => 'users',
            'token' => 'abc123',
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $result = $this->replacer->replace($template, $parameters);

        $this->assertEquals('https://api.example.com/users', $result['url']);
        $this->assertEquals('Bearer abc123', $result['headers']['Authorization']);
        $this->assertEquals('John Doe', $result['body']['name']);
        $this->assertEquals('john@example.com', $result['body']['email']);
    }

    public function test_replace_with_missing_parameters()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}/{{id}}',
            'token' => '{{token}}'
        ];
        $parameters = ['endpoint' => 'users']; // Missing 'id' and 'token'

        $result = $this->replacer->replace($template, $parameters);

        $this->assertEquals('https://api.example.com/users/{{id}}', $result['url']);
        $this->assertEquals('{{token}}', $result['token']);
    }

    public function test_replace_with_numeric_values()
    {
        $template = [
            'limit' => '{{limit}}',
            'page' => '{{page}}'
        ];
        $parameters = [
            'limit' => 10,
            'page' => 2
        ];

        $result = $this->replacer->replace($template, $parameters);

        $this->assertEquals('10', $result['limit']);
        $this->assertEquals('2', $result['page']);
    }

    public function test_extract_parameter_names()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}/{{id}}',
            'headers' => [
                'Authorization' => 'Bearer {{token}}'
            ],
            'body' => [
                'name' => '{{name}}',
                'status' => 'active'
            ]
        ];

        $parameterNames = $this->replacer->extractParameterNames($template);

        $this->assertCount(4, $parameterNames);
        $this->assertContains('endpoint', $parameterNames);
        $this->assertContains('id', $parameterNames);
        $this->assertContains('token', $parameterNames);
        $this->assertContains('name', $parameterNames);
    }

    public function test_extract_parameter_names_with_duplicates()
    {
        $template = [
            'url' => 'https://api.example.com/{{id}}/details/{{id}}',
            'backup_url' => 'https://backup.example.com/{{id}}'
        ];

        $parameterNames = $this->replacer->extractParameterNames($template);

        $this->assertCount(1, $parameterNames);
        $this->assertContains('id', $parameterNames);
    }

    public function test_extract_parameter_names_with_no_parameters()
    {
        $template = [
            'url' => 'https://api.example.com/static',
            'method' => 'GET'
        ];

        $parameterNames = $this->replacer->extractParameterNames($template);

        $this->assertEmpty($parameterNames);
    }

    public function test_validate_parameters_with_all_required()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}/{{id}}',
            'token' => '{{token}}'
        ];
        $parameters = [
            'endpoint' => 'users',
            'id' => '123',
            'token' => 'abc123'
        ];

        // Should not throw exception
        $this->replacer->validateParameters($template, $parameters);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function test_validate_parameters_with_missing_required()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}/{{id}}',
            'token' => '{{token}}'
        ];
        $parameters = [
            'endpoint' => 'users'
            // Missing 'id' and 'token'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters: id, token');

        $this->replacer->validateParameters($template, $parameters);
    }

    public function test_validate_parameters_with_extra_parameters()
    {
        $template = [
            'url' => 'https://api.example.com/{{endpoint}}'
        ];
        $parameters = [
            'endpoint' => 'users',
            'extra_param' => 'value'
        ];

        // Should not throw exception for extra parameters
        $this->replacer->validateParameters($template, $parameters);
        $this->assertTrue(true);
    }

    public function test_replace_with_special_characters()
    {
        $template = [
            'query' => 'SELECT * FROM users WHERE name = "{{name}}" AND email LIKE "%{{domain}}%"'
        ];
        $parameters = [
            'name' => "John's Account",
            'domain' => 'example.com'
        ];

        $result = $this->replacer->replace($template, $parameters);

        $this->assertEquals('SELECT * FROM users WHERE name = "John\'s Account" AND email LIKE "%example.com%"', $result['query']);
    }
}
