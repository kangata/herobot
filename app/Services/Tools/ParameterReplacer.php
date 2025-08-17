<?php

namespace App\Services\Tools;

class ParameterReplacer
{
    /**
     * Replace template parameters in configuration with actual values.
     * 
     * @param array $template The template configuration with {{parameter}} placeholders
     * @param array $parameters The actual parameter values
     * @return array The configuration with replaced values
     */
    public function replace(array $template, array $parameters): array
    {
        $json = json_encode($template);
        
        foreach ($parameters as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $json = str_replace($placeholder, $value, $json);
        }
        
        return json_decode($json, true);
    }

    /**
     * Extract parameter names from a template.
     * 
     * @param array $template The template configuration
     * @return array List of parameter names found in the template
     */
    public function extractParameterNames(array $template): array
    {
        $json = json_encode($template);
        preg_match_all('/\{\{([^}]+)\}\}/', $json, $matches);
        
        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate that all required parameters are provided.
     * 
     * @param array $template The template configuration
     * @param array $parameters The provided parameters
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function validateParameters(array $template, array $parameters): void
    {
        $requiredParams = $this->extractParameterNames($template);
        $missingParams = array_diff($requiredParams, array_keys($parameters));
        
        if (!empty($missingParams)) {
            throw new \InvalidArgumentException(
                'Missing required parameters: ' . implode(', ', $missingParams)
            );
        }
    }
}
