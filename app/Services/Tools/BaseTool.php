<?php

namespace App\Services\Tools;

abstract class BaseTool
{
    protected string $name;
    protected string $description;
    protected array $parametersSchema;

    public function __construct(protected array $config = [])
    {
        $this->initialize();
    }

    abstract protected function initialize(): void;

    abstract public function execute(array $parameters): array;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParametersSchema(): array
    {
        return $this->parametersSchema;
    }

    protected function validateParameters(array $parameters): void
    {
        // Basic validation - can be enhanced with JSON Schema validation
        $required = $this->parametersSchema['required'] ?? [];
        
        foreach ($required as $field) {
            if (!isset($parameters[$field])) {
                throw new \InvalidArgumentException("Required parameter '{$field}' is missing");
            }
        }
    }
}
