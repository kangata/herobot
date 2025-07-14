<?php

namespace App\Services\Traits;

trait SimilarityCalculationTrait
{
    /**
     * Calculate similarity between two vectors using fast C extension if available,
     * otherwise fallback to PHP implementation.
     */
    protected function calculateSimilarity($vector1, $vector2)
    {
        if (function_exists('fast_cosine_similarity')) {
            return fast_cosine_similarity($vector1, $vector2);
        }

        return $this->cosineSimilarity($vector1, $vector2);
    }

    /**
     * Calculate cosine similarity between two vectors using PHP implementation.
     */
    protected function cosineSimilarity($vector1, $vector2)
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($vector1 as $i => $value) {
            $dotProduct += $value * $vector2[$i];
            $norm1 += $value * $value;
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        return $dotProduct / ($norm1 * $norm2);
    }
}
