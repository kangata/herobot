<?php

namespace App\Services\Traits;

use App\Models\Bot;
use App\Services\AIServiceFactory;
use App\Services\TokenPricingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait AIServiceHelperTrait
{
    /**
     * Estimate token count for text content
     *
     * @param string $text
     * @return int
     */
    protected function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters for most languages
        // This is a conservative estimate, actual tokenization may vary
        return max(1, (int) ceil(strlen($text) / 4));
    }

    /**
     * Build system prompt with bot prompt and relevant knowledge
     *
     * @param Bot $bot Bot instance with prompt property
     * @param Collection|null $relevantKnowledge Collection of relevant knowledge items
     * @param bool $useEstimate Whether to use estimated knowledge for credit calculation
     * @return string Complete system prompt
     */
    protected function buildSystemPrompt(Bot $bot, ?Collection $relevantKnowledge = null, bool $useEstimate = false): string
    {
        $systemPrompt = $bot->prompt;
        
        if ($useEstimate) {
            // For credit estimation - use conservative estimate
            $knowledgeCount = $bot->knowledge()->where('status', 'completed')->count();
            if ($knowledgeCount > 0) {
                $systemPrompt .= "\n\nUse the following information to answer questions:\n\n";
                // Estimate average knowledge chunk size
                $systemPrompt .= str_repeat("[Knowledge context placeholder] ", 200); // ~800 characters
            }
        } else {
            // For actual usage - use real knowledge
            if ($relevantKnowledge && $relevantKnowledge->isNotEmpty()) {
                $systemPrompt .= "\n\nUse the following information to answer questions:\n\n";
                foreach ($relevantKnowledge as $knowledge) {
                    $systemPrompt .= "{$knowledge['text']}\n\n";
                }
            }
        }
        
        return $systemPrompt;
    }

    /**
     * Get AI services (chat and embedding)
     *
     * @param Bot|null $bot
     * @return array
     */
    protected function getAIServices(?Bot $bot = null): array
    {
        try {
            return [
                'chat' => AIServiceFactory::createChatService($bot),
                'embedding' => AIServiceFactory::createEmbeddingService($bot),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create AI services: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate credits for token usage
     *
     * @param TokenPricingService $tokenPricingService
     * @param string $provider
     * @param string $model
     * @param int $inputTokens
     * @param int $outputTokens
     * @return float
     */
    protected function calculateCreditsForTokens(
        TokenPricingService $tokenPricingService,
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens
    ): float {
        return $tokenPricingService->calculateCost($provider, $model, $inputTokens, $outputTokens);
    }

    /**
     * Check if MIME type is audio
     *
     * @param string $mimeType
     * @return bool
     */
    protected function isAudioMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'audio/');
    }

    /**
     * Check if MIME type is image
     *
     * @param string $mimeType
     * @return bool
     */
    protected function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if MIME type is video
     *
     * @param string $mimeType
     * @return bool
     */
    protected function isVideoMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    /**
     * Check if MIME type requires vision processing
     *
     * @param string $mimeType
     * @return bool
     */
    protected function requiresVisionProcessing(string $mimeType): bool
    {
        return $this->isImageMimeType($mimeType) || $this->isVideoMimeType($mimeType);
    }

    /**
     * Generate default prompt based on media MIME type
     *
     * @param string $mimeType
     * @return string
     */
    protected function generateDefaultPromptForMimeType(string $mimeType): string
    {
        if ($this->isImageMimeType($mimeType)) {
            return 'Please respond based on the attached image.';
        }

        if ($this->isAudioMimeType($mimeType)) {
            return 'Please respond based on the attached audio.';
        }

        if ($this->isVideoMimeType($mimeType)) {
            return 'Please respond based on the attached video.';
        }

        return 'Please respond based on the attached document.';
    }

    /**
     * Get conservative token estimate for transcription
     *
     * @return int
     */
    protected function getTranscriptionTokenEstimate(): int
    {
        return 500; // Conservative estimate for audio transcription
    }

    /**
     * Get conservative token estimate for response
     *
     * @return int
     */
    protected function getResponseTokenEstimate(): int
    {
        return 300; // Conservative estimate for response length
    }

    /**
     * Apply safety margin to credit calculation
     *
     * @param float $credits
     * @param float $margin
     * @return float
     */
    protected function applySafetyMargin(float $credits, float $margin = 0.1): float
    {
        return $credits * (1 + $margin);
    }

    /**
     * Apply tool call buffer to credit calculation
     *
     * @param float $credits
     * @param int $toolCount
     * @param float $buffer
     * @return float
     */
    protected function applyToolCallBuffer(float $credits, int $toolCount, float $buffer = 0.2): float
    {
        return $toolCount > 0 ? $credits * (1 + $buffer) : $credits;
    }
}
