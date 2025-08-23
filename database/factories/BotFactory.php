<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bot>
 */
class BotFactory extends Factory
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
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'prompt' => $this->faker->paragraph,
            'ai_chat_service' => 'openai/gpt-4',
            'ai_embedding_service' => 'openai/text-embedding-3-small',
            'ai_speech_to_text_service' => 'openai/whisper-1',
            'openai_api_key' => null,
            'gemini_api_key' => null,
        ];
    }
}
