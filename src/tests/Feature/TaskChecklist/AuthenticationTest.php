<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * チェックリスト：認証が正しく機能するか
 * （ログイン・ログアウト・会員登録そのものは tests/Feature/Auth で確認）
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedRoutes(): array
    {
        return [
            '一覧' => ['get', 'task-checklists.index', false],
            '自分の業務' => ['get', 'task-checklists.mine', false],
            '作成フォーム' => ['get', 'task-checklists.create', false],
            '保存' => ['post', 'task-checklists.store', false],
            '詳細' => ['get', 'task-checklists.show', true],
            '編集フォーム' => ['get', 'task-checklists.edit', true],
            '更新' => ['put', 'task-checklists.update', true],
            '削除' => ['delete', 'task-checklists.destroy', true],
            'ダッシュボード' => ['get', 'dashboard', false],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_to_login(string $method, string $route, bool $needsTask): void
    {
        $params = $needsTask ? [TaskChecklist::factory()->create()] : [];

        $this->{$method}(route($route, $params))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_task_checklist(): void
    {
        $this->post(route('task-checklists.store'), [
            'title' => 'ゲストの投稿',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
        ]);

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_top_page_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_top_page_redirects_authenticated_user_to_index(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('task-checklists.index'));
    }

    public function test_dashboard_redirects_to_index(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('task-checklists.index'));
    }

    public function test_user_can_access_index_after_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('task-checklists.index'))->assertOk();
    }

    public function test_user_cannot_access_index_after_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');
        $this->assertGuest();

        $this->get(route('task-checklists.index'))->assertRedirect(route('login'));
    }
}
