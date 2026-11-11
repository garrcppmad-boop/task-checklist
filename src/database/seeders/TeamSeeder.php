<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

/**
 * チームの初期データ（docs/02_database-design/migration-plan.md「3. 初期データ」）
 * 会員登録でチームを選ぶために必須なので、本番環境でも実行する
 *   php artisan db:seed --class=TeamSeeder --force
 * firstOrCreate なので、何回実行しても同じチームが二重に登録されない
 */
class TeamSeeder extends Seeder
{
    public const NAMES = ['第1営業', '第2営業', '営業事務'];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Team::firstOrCreate(['name' => $name]);
        }
    }
}
