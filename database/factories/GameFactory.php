<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * A game started long enough ago to have reached its last month.
     */
    public function played(): static
    {
        return $this->state(['created_at' => now()->subMinutes(4)]);
    }
}
