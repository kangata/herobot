<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
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
            'name' => $this->faker->company . ' Channel',
            'type' => $this->faker->randomElement(['whatsapp', 'telegram', 'discord', 'slack']),
            'phone' => $this->faker->phoneNumber,
            'is_connected' => $this->faker->boolean(80),
        ];
    }

    /**
     * Indicate that the channel is connected.
     */
    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_connected' => true,
        ]);
    }

    /**
     * Indicate that the channel is disconnected.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_connected' => false,
        ]);
    }
}
