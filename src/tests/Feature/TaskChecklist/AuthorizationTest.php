<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * チェックリスト：権限チェックが適切か（他人のデータを編集できないか）
 * - 同じチームのメンバー：閲覧はできるが、編集・削除はできない
 * - 他のチームのメンバー：閲覧もできない（一覧にも出ない）
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $teammate;
    private User $outsider;
    private TaskChecklist $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->teammate = User::factory()->create(['team_id' => $this->owner->team_id]);
        $this->outsider = User::factory()->create(); // 別のチーム
        $this->task = TaskChecklist::factory()->for($this->owner)->create(['title' => '元のタイトル']);
    }

    private function updateData(): array
    {
        return [
            'title' => '書き換えられたタイトル',
            'due_date' => today()->toDateString(),
            'status' => 'done',
        ];
    }

    // ───────── 同じチームの他人 ─────────

    public function test_teammate_can_view_task_checklist(): void
    {
        $this->actingAs($this->teammate)
            ->get(route('task-checklists.show', $this->task))
            ->assertOk();
    }

    public function test_teammate_does_not_see_edit_and_delete_buttons(): void
    {
        $this->actingAs($this->teammate)
            ->get(route('task-checklists.show', $this->task))
            ->assertDontSee(route('task-checklists.edit', $this->task))
            ->assertSee('編集・削除は投稿者だけができます');
    }

    public function test_owner_sees_edit_and_delete_buttons(): void
    {
        $this->actingAs($this->owner)
            ->get(route('task-checklists.show', $this->task))
            ->assertSee(route('task-checklists.edit', $this->task));
    }

    public function test_teammate_cannot_open_edit_form(): void
    {
        $this->actingAs($this->teammate)
            ->get(route('task-checklists.edit', $this->task))
            ->assertForbidden();
    }

    public function test_teammate_cannot_update_task_checklist(): void
    {
        $this->actingAs($this->teammate)
            ->put(route('task-checklists.update', $this->task), $this->updateData())
            ->assertForbidden();

        $this->assertSame('元のタイトル', $this->task->fresh()->title);
    }

    public function test_teammate_cannot_delete_task_checklist(): void
    {
        $this->actingAs($this->teammate)
            ->delete(route('task-checklists.destroy', $this->task))
            ->assertForbidden();

        $this->assertModelExists($this->task);
    }

    // ───────── 他のチーム ─────────

    public function test_outsider_cannot_view_task_checklist(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('task-checklists.show', $this->task))
            ->assertForbidden();
    }

    public function test_outsider_cannot_see_task_checklist_in_index(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('task-checklists.index'))
            ->assertOk()
            ->assertDontSee('元のタイトル');
    }

    public function test_outsider_cannot_update_task_checklist(): void
    {
        $this->actingAs($this->outsider)
            ->put(route('task-checklists.update', $this->task), $this->updateData())
            ->assertForbidden();

        $this->assertSame('元のタイトル', $this->task->fresh()->title);
    }

    public function test_outsider_cannot_delete_task_checklist(): void
    {
        $this->actingAs($this->outsider)
            ->delete(route('task-checklists.destroy', $this->task))
            ->assertForbidden();

        $this->assertModelExists($this->task);
    }

    // ───────── 投稿者を書き換えられない ─────────

    public function test_owner_cannot_transfer_task_checklist_via_user_id(): void
    {
        $this->actingAs($this->owner)
            ->put(route('task-checklists.update', $this->task), $this->updateData() + ['user_id' => $this->teammate->id]);

        $this->assertSame($this->owner->id, $this->task->fresh()->user_id);
    }
}
