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
            // teams.name は重複できないので、番号を付けて重ならないようにする
            'name' => 'テストチーム'.fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
