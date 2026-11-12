<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 初期データ（docs/02_database-design/migration-plan.md「3. 初期データ」）
     * - TeamSeeder：チーム（本番でも必要）
     * - DemoSeeder：動作確認用のユーザー・業務など（開発環境だけ）
     */
    public function run(): void
    {
        $this->call(TeamSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
