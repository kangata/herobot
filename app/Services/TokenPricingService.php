<?php

namespace App\Services;

class TokenPricingService
{
    /**
     * Get pricing data from configuration.
     * 1 Credit = 1 Rupiah, 16,500 Credits = 1 USD
     */
    private function getPricingConfig(): array
    {
        $config = config('ai-models.providers');
        $pricing = [];
        
        foreach ($config as $providerId => $provider) {
            $providerName = ucfirst($providerId) === 'Openai' ? 'OpenAI' : $provider['name'];
            $pricing[$providerName] = [];
            
            foreach ($provider['models'] as $modelId => $model) {
                $pricing[$providerName][$modelId] = $model['pricing'];
                
                // Add type for audio models
                if (isset($model['type'])) {
                    $pricing[$providerName][$modelId]['type'] = $model['type'];
                }
            }
        }
        
        return $pricing;
    }

    /**
     * Calculate the cost in credits for token usage.
     *
     * @param string $provider The AI provider (OpenAI, Gemini)
     * @param string $model The model name
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @return float Cost in credits
     */
    public function calculateCost(string $provider, string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = $this->getPricing($provider, $model);
        
        if (!$pricing) {
            // Fallback pricing if model not found
            $inputCost = ($inputTokens / 1000000) * 1000; // 1000 credits per 1M tokens
            $outputCost = ($outputTokens / 1000000) * 5000; // 5000 credits per 1M tokens
            return round($inputCost + $outputCost, 6);
        }

        // Convert USD pricing to credits (1 USD = 16,500 credits)
        $inputCostUsd = ($inputTokens / 1000000) * $pricing['input'];
        $outputCostUsd = ($outputTokens / 1000000) * $pricing['output'];
        $totalCostUsd = $inputCostUsd + $outputCostUsd;
        
        // Convert to credits
        $totalCostCredits = $totalCostUsd * 16500;

        return round($totalCostCredits, 6);
    }

    /**
     * Calculate the cost in credits for audio usage (per minute).
     *
     * @param string $provider The AI provider (OpenAI, Gemini)
     * @param string $model The model name
     * @param float $minutes Number of minutes of audio
     * @return float Cost in credits
     */
    public function calculateAudioCost(string $provider, string $model, float $minutes): float
    {
        $pricing = $this->getPricing($provider, $model);
        
        if (!$pricing || !$this->isAudioModel($provider, $model)) {
            // Fallback pricing for audio if model not found
            return round($minutes * 0.006 * 16500, 6); // Default to Whisper pricing
        }

        // Convert USD pricing to credits (1 USD = 16,500 credits)
        $totalCostUsd = $minutes * $pricing['input'];
        $totalCostCredits = $totalCostUsd * 16500;

        return round($totalCostCredits, 6);
    }

    /**
     * Check if a model is an audio model.
     *
     * @param string $provider
     * @param string $model
     * @return bool
     */
    public function isAudioModel(string $provider, string $model): bool
    {
        $pricing = $this->getPricing($provider, $model);
        return $pricing && isset($pricing['type']) && $pricing['type'] === 'audio';
    }

    /**
     * Get pricing for a specific provider and model.
     *
     * @param string $provider
     * @param string $model
     * @return array|null
     */
    public function getPricing(string $provider, string $model): ?array
    {
        $pricing = $this->getPricingConfig();
        return $pricing[$provider][$model] ?? null;
    }

    /**
     * Get all available pricing.
     *
     * @return array
     */
    public function getAllPricing(): array
    {
        return $this->getPricingConfig();
    }

    /**
     * Check if a provider and model combination is supported.
     *
     * @param string $provider
     * @param string $model
     * @return bool
     */
    public function isSupported(string $provider, string $model): bool
    {
        $pricing = $this->getPricingConfig();
        return isset($pricing[$provider][$model]);
    }

    /**
     * Get the input token price for a model.
     *
     * @param string $provider
     * @param string $model
     * @return float Credits per 1M tokens
     */
    public function getInputPrice(string $provider, string $model): float
    {
        $pricing = $this->getPricing($provider, $model);
        if (!$pricing) {
            return 1000; // Fallback in credits
        }
        // Convert USD to credits (1 USD = 16,500 credits)
        return $pricing['input'] * 16500;
    }

    /**
     * Get the output token price for a model.
     *
     * @param string $provider
     * @param string $model
     * @return float Credits per 1M tokens
     */
    public function getOutputPrice(string $provider, string $model): float
    {
        $pricing = $this->getPricing($provider, $model);
        if (!$pricing) {
            return 5000; // Fallback in credits
        }
        // Convert USD to credits (1 USD = 16,500 credits)
        return $pricing['output'] * 16500;
    }

    /**
     * Format credits to display with currency.
     *
     * @param float $credits
     * @return string
     */
    public function formatCredits(float $credits): string
    {
        return number_format($credits, 6) . ' credits';
    }

    /**
     * Convert credits to USD.
     *
     * @param float $credits
     * @return float
     */
    public function creditsToUsd(float $credits): float
    {
        return round($credits / 16500, 4);
    }

    /**
     * Convert USD to credits.
     *
     * @param float $usd
     * @return float
     */
    public function usdToCredits(float $usd): float
    {
        return round($usd * 16500, 6);
    }
}
