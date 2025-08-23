<?php

namespace Database\Factories;

use App\Models\Balance;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Balance>
 */
class BalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'amount' => $this->faker->numberBetween(100000000, 1000000000), // 100-1000 credits (in micro-units)
        ];
    }

    /**
     * Indicate that the balance is low.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(1000, 10000), // 0.001-0.01 credits
        ]);
    }

    /**
     * Indicate that the balance is high.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(1000000000, 10000000000), // 1000-10000 credits
        ]);
    }

    /**
     * Set a specific amount in credits (will be converted to micro-units).
     */
    public function withCredits(float $credits): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => round($credits * 1000000),
        ]);
    }
}
