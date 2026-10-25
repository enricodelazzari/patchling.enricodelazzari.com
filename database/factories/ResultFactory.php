<?php

namespace Database\Factories;

use App\Enums\Verdict;
use App\Models\Game;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory()->played(),
            'name' => fake()->firstName(),
            'claim_token' => hash('sha256', 'secret'),
            'score' => fake()->numberBetween(500, 8000),
            'verdict' => Verdict::Survived,
            'months' => 36,
            'revenue' => fake()->numberBetween(50, 600),
            'features' => fake()->numberBetween(3, 20),
            'squashed' => fake()->numberBetween(0, 40),
            'breaches' => 0,
            'health' => 70,
            'happy' => 60,
        ];
    }

    public function anonymous(): static
    {
        return $this->state(['name' => null]);
    }
}
