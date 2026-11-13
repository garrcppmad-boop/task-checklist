<?php

namespace Tests\Feature\Security;

use App\Models\Comment;
use App\Models\TaskChecklist;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * セキュリティ：XSS 対策（{{ }} でエスケープ）
 * ユーザーが入力できる値に <script> を入れても、HTML としてではなく文字として表示されることを確かめる
 */
class XssTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<script>alert("xss")</script>';
    private const ATTR_PAYLOAD = '"><img src=x onerror=alert(1)>';

    private User $user;
    private TaskChecklist $task;

    protected function setUp(): void
    {
        parent::setUp();

        $team = Team::factory()->create(['name' => self::PAYLOAD]);
        $this->user = User::factory()->for($team)->create(['name' => self::PAYLOAD]);
        $this->task = TaskChecklist::factory()->for($this->user)->create([
            'title' => self::PAYLOAD.self::ATTR_PAYLOAD,
            'description' => self::PAYLOAD,
        ]);
        Comment::factory()->for($this->task)->for($this->user)->create(['body' => self::PAYLOAD]);
    }

    private function assertEscaped($response): void
    {
        $response->assertOk()
            ->assertDontSee(self::PAYLOAD, false)          // 生の <script> が出ていない
            ->assertDontSee('<img src=x onerror', false)  // 属性から抜け出したタグが出ていない
            ->assertSee(e(self::PAYLOAD), false);          // エスケープされた形で出ている
    }

    public function test_index_escapes_user_input(): void
    {
        $this->assertEscaped($this->actingAs($this->user)->get(route('task-checklists.index')));
    }

    public function test_mine_escapes_user_input(): void
    {
        $this->assertEscaped($this->actingAs($this->user)->get(route('task-checklists.mine')));
    }

    public function test_show_escapes_title_description_comment_and_name(): void
    {
        $response = $this->actingAs($this->user)->get(route('task-checklists.show', $this->task));

        $this->assertEscaped($response);
        // タイトル・説明・コメント・投稿者名・コメント投稿者名の5か所以上でエスケープされている
        $this->assertGreaterThanOrEqual(5, substr_count($response->getContent(), e(self::PAYLOAD)));
    }

    public function test_edit_form_escapes_value_attribute(): void
    {
        $this->assertEscaped($this->actingAs($this->user)->get(route('task-checklists.edit', $this->task)));
    }

    public function test_search_keyword_is_escaped_when_reflected(): void
    {
        $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['keyword' => self::ATTR_PAYLOAD]))
            ->assertOk()
            ->assertDontSee('<img src=x onerror', false)
            ->assertSee('value="'.e(self::ATTR_PAYLOAD).'"', false);
    }

    public function test_old_input_is_escaped_after_validation_error(): void
    {
        $this->actingAs($this->user)
            ->from(route('task-checklists.create'))
            ->followingRedirects()
            ->post(route('task-checklists.store'), [
                'title' => self::ATTR_PAYLOAD,
                'description' => self::PAYLOAD,
                'due_date' => '',
                'status' => 'todo',
            ])
            ->assertOk()
            ->assertDontSee(self::PAYLOAD, false)
            ->assertDontSee('<img src=x onerror', false);
    }

    public function test_registration_form_escapes_team_names(): void
    {
        auth()->logout();

        $this->get('/register')
            ->assertOk()
            ->assertDontSee(self::PAYLOAD, false)
            ->assertSee(e(self::PAYLOAD), false);
    }
}
