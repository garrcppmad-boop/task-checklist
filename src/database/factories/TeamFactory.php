<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            // teams.name は unique・50文字まで
            'name' => fake()->unique()->bothify('チーム-####'),
        ];
    }
}
