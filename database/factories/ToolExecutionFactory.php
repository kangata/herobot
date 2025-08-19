<?php

namespace Database\Factories;

use App\Models\Tool;
use App\Models\ToolExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ToolExecution>
 */
class ToolExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tool_id' => Tool::factory(),
            'chat_history_id' => null,
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'input_parameters' => [
                'param1' => $this->faker->word,
                'param2' => $this->faker->randomNumber(),
            ],
            'output' => [
                'success' => $this->faker->boolean,
                'data' => $this->faker->sentence,
            ],
            'error' => $this->faker->optional()->sentence,
        ];
    }
}
