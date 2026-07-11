<?php

namespace Database\Factories;

use App\Models\Highscore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Highscore>
 */
class HighscoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->firstName(),
            'stars' => fake()->numberBetween(1, 69),
        ];
    }
}
