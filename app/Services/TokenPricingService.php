<?php

namespace App\Services;

class TokenPricingService
{
    /**
     * Token pricing in credits per 1M tokens.
     * 1 Credit = 1 Rupiah, 16,500 Credits = 1 USD
     */
    private const PRICING = [
        'OpenAI' => [
            // GPT-5 Series
            'gpt-5' => ['input' => 20625, 'output' => 165000],
            'gpt-5-mini' => ['input' => 4125, 'output' => 33000],
            'gpt-5-nano' => ['input' => 825, 'output' => 6600],
            'gpt-5-chat-latest' => ['input' => 20625, 'output' => 165000],
            
            // GPT-4.1 Series
            'gpt-4.1' => ['input' => 33000, 'output' => 132000],
            'gpt-4.1-mini' => ['input' => 6600, 'output' => 26400],
            'gpt-4.1-nano' => ['input' => 1650, 'output' => 6600],
            
            // GPT-4o Series
            'gpt-4o' => ['input' => 41250, 'output' => 165000],
            'gpt-4o-mini' => ['input' => 825, 'output' => 3300], // Based on current pricing
            
            // Embeddings
            'text-embedding-3-small' => ['input' => 165, 'output' => 0],
            'text-embedding-3-large' => ['input' => 1072.5, 'output' => 0],
            'text-embedding-ada-002' => ['input' => 825, 'output' => 0],
        ],
        'Gemini' => [
            // Gemini 2.5 Series
            'gemini-2.5-pro' => ['input' => 20625, 'output' => 165000],
            'gemini-2.5-flash' => ['input' => 4950, 'output' => 41250],
            'gemini-2.5-flash-lite' => ['input' => 1650, 'output' => 6600],
            
            // Embeddings
            'text-embedding-004' => ['input' => 2475, 'output' => 0],
        ],
    ];

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

        $inputCost = ($inputTokens / 1000000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
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
        return self::PRICING[$provider][$model] ?? null;
    }

    /**
     * Get all available pricing.
     *
     * @return array
     */
    public function getAllPricing(): array
    {
        return self::PRICING;
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
        return isset(self::PRICING[$provider][$model]);
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
        return $pricing ? $pricing['input'] : 1000; // Fallback
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
        return $pricing ? $pricing['output'] : 5000; // Fallback
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
