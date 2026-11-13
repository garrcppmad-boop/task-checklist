<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\TaskChecklist;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 動作確認用のデータ（開発環境だけ。本番では実行しない）
 * docs/02_database-design/migration-plan.md「DemoSeeder で用意するデータ」
 *
 * - 各チームに3人ずつ：demo1@example.com 〜 demo9@example.com（パスワードはすべて password）
 *   demo1〜3：第1営業、demo4〜6：第2営業、demo7〜9：営業事務
 * - 期限切れ・期限間近・通常・完了の業務を数件ずつ（F-13 の色分けの確認用）
 * - 第1営業には25件以上の業務（ページネーション 20件 の確認用）
 * - 名前・メールアドレスは架空のもの（実在の会社名・個人情報は使わない）
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $number = 0;

        foreach (Team::whereIn('name', TeamSeeder::NAMES)->orderBy('id')->get() as $teamIndex => $team) {
            $members = collect(range(1, 3))->map(function () use ($team, &$number) {
                $number++;

                return User::factory()->create([
                    'team_id' => $team->id,
                    'name' => "デモユーザー{$number}",
                    'email' => "demo{$number}@example.com",
                ]);
            });

            // 第1営業だけ件数を多くして、2ページ目まで表示されるようにする
            $normalCount = $teamIndex === 0 ? 16 : 3;

            foreach ($members as $member) {
                $tasks = collect()
                    ->merge(TaskChecklist::factory()->count(1)->overdue()->for($member)->create())
                    ->merge(TaskChecklist::factory()->count(1)->dueSoon()->for($member)->create())
                    ->merge(TaskChecklist::factory()->count(intdiv($normalCount, 3) + 1)->for($member)->create())
                    ->merge(TaskChecklist::factory()->count(1)->done()->for($member)->create());

                // 同じチームの他のメンバーがコメント・ブックマークする
                foreach ($tasks->random(2) as $task) {
                    foreach ($members->where('id', '!=', $member->id) as $other) {
                        Comment::factory()->create([
                            'task_checklist_id' => $task->id,
                            'user_id' => $other->id,
                        ]);
                        $other->bookmarks()->syncWithoutDetaching([$task->id]);
                    }
                }
            }
        }
    }
}
