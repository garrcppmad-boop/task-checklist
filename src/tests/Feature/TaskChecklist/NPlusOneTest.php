<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\Comment;
use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * チェックリスト：N+1 問題が解決されているか
 * 件数を増やしても、1回の表示で発行される SQL の数が変わらないことを確かめる
 */
class NPlusOneTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** 投稿者がばらばらの業務を作り、それぞれにコメントとブックマークを付ける */
    private function seedTasks(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $author = User::factory()->create(['team_id' => $this->user->team_id]);
            $task = TaskChecklist::factory()->for($author)->create();
            Comment::factory()->for($task)->for($author)->count(2)->create();
            $this->user->bookmarks()->attach($task);
        }
    }

    private function countQueries(string $url): int
    {
        $this->actingAs($this->user);
        $this->app['auth']->guard()->setUser($this->user->fresh());

        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $this->get($url)->assertOk();

        return $count;
    }

    public function test_index_query_count_does_not_grow_with_items(): void
    {
        $this->seedTasks(2);
        $few = $this->countQueries(route('task-checklists.index'));

        $this->seedTasks(10);
        $many = $this->countQueries(route('task-checklists.index'));

        $this->assertSame($few, $many, "一覧の SQL 数が件数に比例して増えている（2件: {$few} 回 / 12件: {$many} 回）");
    }

    public function test_mine_query_count_does_not_grow_with_items(): void
    {
        $makeOwn = function (int $n) {
            TaskChecklist::factory()->for($this->user)->count($n)->create()
                ->each(fn ($t) => Comment::factory()->for($t)->for($this->user)->create());
        };

        $makeOwn(2);
        $few = $this->countQueries(route('task-checklists.mine'));

        $makeOwn(10);
        $many = $this->countQueries(route('task-checklists.mine'));

        $this->assertSame($few, $many, "自分の業務一覧の SQL 数が増えている（{$few} → {$many}）");
    }

    public function test_show_query_count_does_not_grow_with_comments(): void
    {
        $task = TaskChecklist::factory()->for($this->user)->create();
        $addComments = function (int $n) use ($task) {
            foreach (range(1, $n) as $i) {
                $author = User::factory()->create(['team_id' => $this->user->team_id]);
                Comment::factory()->for($task)->for($author)->create();
            }
        };

        $addComments(2);
        $few = $this->countQueries(route('task-checklists.show', $task));

        $addComments(10);
        $many = $this->countQueries(route('task-checklists.show', $task));

        $this->assertSame($few, $many, "詳細画面の SQL 数がコメント数に比例して増えている（{$few} → {$many}）");
    }
}
