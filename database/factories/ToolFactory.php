<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tool>
 */
class ToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['http', 'database']),
            'params' => [
                'url' => $this->faker->url(),
                'method' => 'GET',
                'headers' => ['Content-Type' => 'application/json']
            ],
            'parameters_schema' => [
                'type' => 'object',
                'properties' => [
                    'param1' => ['type' => 'string'],
                    'param2' => ['type' => 'integer']
                ],
                'required' => ['param1']
            ],
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
