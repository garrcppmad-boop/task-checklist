<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 会員登録で選べるチーム（users.team_id は必須）
        $team = Team::firstOrCreate(['name' => '営業チーム']);
        Team::firstOrCreate(['name' => '開発チーム']);

        User::factory()->for($team)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
