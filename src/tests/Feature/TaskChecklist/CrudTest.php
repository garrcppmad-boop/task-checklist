<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\Comment;
use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * チェックリスト：全CRUD操作が正常に動作するか
 */
class CrudTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => '見積書を作る',
            'description' => "A社向け\n2行目",
            'due_date' => today()->addWeek()->toDateString(),
            'status' => 'doing',
        ], $overrides);
    }

    // ───────── Create ─────────

    public function test_create_form_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('task-checklists.create'))
            ->assertOk()
            ->assertSee('業務の新規投稿');
    }

    public function test_task_checklist_can_be_stored(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('task-checklists.store'), $this->validData());

        $task = TaskChecklist::sole();
        $response->assertRedirect(route('task-checklists.show', $task))
            ->assertSessionHas('success');

        $this->assertSame($user->id, $task->user_id);
        $this->assertSame('見積書を作る', $task->title);
        $this->assertSame('doing', $task->status);
    }

    public function test_user_id_cannot_be_overwritten_from_request(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['team_id' => $user->team_id]);

        $this->actingAs($user)
            ->post(route('task-checklists.store'), $this->validData(['user_id' => $other->id]));

        $this->assertSame($user->id, TaskChecklist::sole()->user_id);
    }

    // ───────── Read ─────────

    public function test_index_lists_team_task_checklists(): void
    {
        $user = User::factory()->create();
        $mate = User::factory()->create(['team_id' => $user->team_id]);
        TaskChecklist::factory()->for($user)->create(['title' => '自分の業務']);
        TaskChecklist::factory()->for($mate)->create(['title' => '同僚の業務']);

        $this->actingAs($user)
            ->get(route('task-checklists.index'))
            ->assertOk()
            ->assertSee('自分の業務')
            ->assertSee('同僚の業務');
    }

    public function test_index_is_ordered_by_due_date(): void
    {
        $user = User::factory()->create();
        TaskChecklist::factory()->for($user)->create(['title' => '来月の業務', 'due_date' => today()->addMonth()]);
        TaskChecklist::factory()->for($user)->create(['title' => '明日の業務', 'due_date' => today()->addDay()]);

        $this->actingAs($user)
            ->get(route('task-checklists.index'))
            ->assertSeeInOrder(['明日の業務', '来月の業務']);
    }

    public function test_mine_lists_only_own_task_checklists(): void
    {
        $user = User::factory()->create();
        $mate = User::factory()->create(['team_id' => $user->team_id]);
        TaskChecklist::factory()->for($user)->create(['title' => '自分の業務']);
        TaskChecklist::factory()->for($mate)->create(['title' => '同僚の業務']);

        $this->actingAs($user)
            ->get(route('task-checklists.mine'))
            ->assertOk()
            ->assertSee('自分の業務')
            ->assertDontSee('同僚の業務');
    }

    public function test_show_displays_task_checklist_and_comments(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create(['title' => '詳細の業務']);
        Comment::factory()->for($task)->for($user)->create(['body' => '確認しました']);

        $this->actingAs($user)
            ->get(route('task-checklists.show', $task))
            ->assertOk()
            ->assertSee('詳細の業務')
            ->assertSee('確認しました');
    }

    public function test_show_returns_404_for_missing_task_checklist(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('task-checklists.show', 99999))
            ->assertNotFound();
    }

    // ───────── Update ─────────

    public function test_edit_form_is_displayed_with_current_values(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create(['title' => '編集前のタイトル']);

        $this->actingAs($user)
            ->get(route('task-checklists.edit', $task))
            ->assertOk()
            ->assertSee('編集前のタイトル');
    }

    public function test_task_checklist_can_be_updated(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('task-checklists.update', $task), $this->validData(['title' => '更新後', 'status' => 'done']))
            ->assertRedirect(route('task-checklists.show', $task))
            ->assertSessionHas('success');

        $task->refresh();
        $this->assertSame('更新後', $task->title);
        $this->assertSame('done', $task->status);
    }

    // ───────── Delete ─────────

    public function test_task_checklist_can_be_deleted_with_comments_and_bookmarks(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create();
        Comment::factory()->for($task)->for($user)->create();
        $user->bookmarks()->attach($task);

        $this->actingAs($user)
            ->delete(route('task-checklists.destroy', $task))
            ->assertRedirect(route('task-checklists.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($task);
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('bookmarks', 0);
    }
}
