<?php

namespace Database\Factories;

use App\Game\Levels;
use App\Models\LevelAttempt;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<LevelAttempt>
 */
class LevelAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'level_id' => Arr::random(Levels::all())->id,
            'mistakes' => 0,
            'hint_used' => false,
            'made_connections' => [],
        ];
    }
}
