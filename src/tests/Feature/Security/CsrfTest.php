<?php

namespace Tests\Feature\Security;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * セキュリティ：CSRF 保護が有効か
 *
 * Laravel はテスト実行中（APP_ENV=testing）だけ CSRF チェックを自動で外す。
 * ここでは環境を local に切り替えて、本番と同じく CSRF チェックが働く状態で確かめる。
 */
class CsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'local';
    }

    private function taskData(): array
    {
        return ['title' => 'CSRF', 'due_date' => today()->toDateString(), 'status' => 'todo'];
    }

    public function test_store_without_token_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('task-checklists.store'), $this->taskData())
            ->assertStatus(419);

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_store_with_wrong_token_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['_token' => 'correct-token'])
            ->post(route('task-checklists.store'), $this->taskData() + ['_token' => 'wrong-token'])
            ->assertStatus(419);

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_store_with_valid_token_is_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['_token' => 'correct-token'])
            ->post(route('task-checklists.store'), $this->taskData() + ['_token' => 'correct-token'])
            ->assertRedirect();

        $this->assertDatabaseCount('task_checklists', 1);
    }

    public function test_update_and_delete_without_token_are_rejected(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create(['title' => '元のタイトル']);

        $this->actingAs($user)
            ->put(route('task-checklists.update', $task), ['title' => '改ざん'] + $this->taskData())
            ->assertStatus(419);

        $this->actingAs($user)
            ->delete(route('task-checklists.destroy', $task))
            ->assertStatus(419);

        $this->assertSame('元のタイトル', $task->fresh()->title);
    }

    public function test_auth_forms_without_token_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(419);
        $this->assertGuest();

        $this->actingAs($user)->post('/logout')->assertStatus(419);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(419);
        $this->assertModelExists($user);
    }

    public function test_forms_contain_csrf_token_field(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create();

        foreach ([
            route('task-checklists.create'),
            route('task-checklists.edit', $task),
            route('task-checklists.show', $task),   // 削除フォーム
        ] as $url) {
            $this->actingAs($user)->get($url)->assertSee('name="_token"', false);
        }
    }
}
