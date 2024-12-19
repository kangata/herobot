<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Benchmarks\CosineSimilarityBenchmark;

class RunCosineBenchmark extends Command
{
    protected $signature = 'benchmark:cosine';
    protected $description = 'Run cosine similarity benchmark';

    public function handle()
    {
        $benchmark = new CosineSimilarityBenchmark();
        $results = $benchmark->benchmark();
        
        // Find the fastest time to calculate percentage differences
        $fastestTime = min($results);
        
        $this->table(
            ['Operation', 'Time', 'Comparison'],
            collect($results)->map(function ($time, $name) use ($fastestTime) {
                $timeStr = '';
                if ($time < 1) {
                    $timeStr = number_format($time * 1000, 2) . ' microseconds';
                } elseif ($time < 1000) {
                    $timeStr = number_format($time, 2) . ' milliseconds';
                } else {
                    $timeStr = number_format($time / 1000, 2) . ' seconds';
                }
                
                $percentSlower = $time == $fastestTime ? 
                    'Fastest' : 
                    number_format((($time - $fastestTime) / $fastestTime) * 100, 1) . '% slower';
                
                return [$name, $timeStr, $percentSlower];
            })
        );
    }
} 