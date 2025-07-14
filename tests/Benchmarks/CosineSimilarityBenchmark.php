<?php

namespace Tests\Benchmarks;

use App\Services\Traits\SimilarityCalculationTrait;
use Illuminate\Support\Benchmark;

class CosineSimilarityBenchmark
{
    use SimilarityCalculationTrait;

    private function generateRandomVector($size)
    {
        return array_map(function () {
            return mt_rand() / mt_getrandmax();
        }, range(1, $size));
    }

    public function benchmark()
    {
        $vectorSize = 300;
        $vectorTotal = 1000;
        $vector1 = $this->generateRandomVector($vectorSize);

        $vectors = [];
        for ($i = 0; $i < $vectorTotal; $i++) {
            $vectors[] = $this->generateRandomVector($vectorSize);
        }

        return Benchmark::measure([
            'Fast Cosine Similarity' => function () use ($vector1, $vectors) {
                if (function_exists('fast_cosine_similarity')) {
                    foreach ($vectors as $vector2) {
                        fast_cosine_similarity($vector1, $vector2);
                    }
                }
            },
            'PHP Cosine Similarity' => function () use ($vector1, $vectors) {
                foreach ($vectors as $vector2) {
                    $this->cosineSimilarity($vector1, $vector2);
                }
            },
        ]);
    }
}
