<?php

namespace Database\Factories;

use App\Game\Levels;
use App\Models\LevelCompletion;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<LevelCompletion>
 */
class LevelCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'level_id' => Arr::random(Levels::all())->id,
            'stars' => fake()->numberBetween(1, 3),
        ];
    }
}
