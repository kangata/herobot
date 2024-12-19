<?php

namespace Tests\Benchmarks;

use App\Services\OpenAIService;
use Illuminate\Support\Benchmark;

class CosineSimilarityBenchmark
{
    private function generateRandomVector($size)
    {
        return array_map(function() {
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
        
        $service = new OpenAIService();

        return Benchmark::measure([
            'Fast Cosine Similarity' => function () use ($vector1, $vectors) {
                if (function_exists('fast_cosine_similarity')) {
                    foreach ($vectors as $vector2) {
                        fast_cosine_similarity($vector1, $vector2);
                    }
                }
            },
            'PHP Cosine Similarity' => function () use ($vector1, $vectors, $service) {
                foreach ($vectors as $vector2) {
                    $service->cosineSimilarity($vector1, $vector2);
                }
            },
        ]);
    }
} 