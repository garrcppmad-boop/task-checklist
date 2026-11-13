<?php

namespace Tests\Feature;

use App\Models\TaskChecklist;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 業務の CRUD（F-04〜F-09）と権限管理（F-15）の Feature テスト
 * 要件定義書「テストコード作成（ログイン、業務のCRUD、Policy による権限チェック）」
 */
class TaskChecklistTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $owner;     // 投稿者

    private User $teammate;  // 同じチームの別のメンバー

    private User $outsider;  // 別のチームのユーザー

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->owner = User::factory()->for($this->team)->create();
        $this->teammate = User::factory()->for($this->team)->create();
        $this->outsider = User::factory()->create(); // 別の新しいチームに所属
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '見積書の作成',
            'description' => '取引先に送る見積書を作る',
            'due_date' => today()->addWeek()->toDateString(),
            'status' => 'todo',
        ], $overrides);
    }

    // ───────── ログインしていないとき ─────────

    public function test_guests_are_redirected_to_login(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create();

        $this->get(route('task-checklists.index'))->assertRedirect(route('login'));
        $this->get(route('task-checklists.show', $task))->assertRedirect(route('login'));
        $this->post(route('task-checklists.store'), $this->validInput())->assertRedirect(route('login'));
    }

    // ───────── 作成（F-04・F-09） ─────────

    public function test_user_can_create_task_checklist_with_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->owner)->post(route('task-checklists.store'), $this->validInput([
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]));

        $task = TaskChecklist::firstOrFail();
        $response->assertRedirect(route('task-checklists.show', $task));
        $this->assertSame($this->owner->id, $task->user_id); // 投稿者はログイン中のユーザー
        Storage::disk('public')->assertExists($task->image_path);
    }

    public function test_create_validation(): void
    {
        $this->actingAs($this->owner)
            ->post(route('task-checklists.store'), $this->validInput([
                'title' => str_repeat('あ', 101), // 100文字まで
                'due_date' => '',
                'status' => 'unknown',
                'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors(['title', 'due_date', 'status', 'image']);

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_image_over_2mb_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('task-checklists.store'), $this->validInput([
                'image' => UploadedFile::fake()->image('big.jpg')->size(2049),
            ]))
            ->assertSessionHasErrors('image');
    }

    // ───────── 一覧・詳細（F-05〜F-07） ─────────

    public function test_team_list_shows_only_my_team_tasks(): void
    {
        TaskChecklist::factory()->for($this->teammate)->create(['title' => '同じチームの業務']);
        TaskChecklist::factory()->for($this->outsider)->create(['title' => '別のチームの業務']);

        $this->actingAs($this->owner)->get(route('task-checklists.index'))
            ->assertOk()
            ->assertSee('同じチームの業務')
            ->assertDontSee('別のチームの業務');
    }

    public function test_my_list_shows_only_my_tasks(): void
    {
        TaskChecklist::factory()->for($this->owner)->create(['title' => '自分の業務']);
        TaskChecklist::factory()->for($this->teammate)->create(['title' => 'メンバーの業務']);

        $this->actingAs($this->owner)->get(route('task-checklists.mine'))
            ->assertOk()
            ->assertSee('自分の業務')
            ->assertDontSee('メンバーの業務');
    }

    public function test_teammate_can_view_but_outsider_gets_403(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create();

        $this->actingAs($this->teammate)->get(route('task-checklists.show', $task))->assertOk();
        $this->actingAs($this->outsider)->get(route('task-checklists.show', $task))->assertForbidden();
    }

    // ───────── 編集・更新（F-08・F-15） ─────────

    public function test_owner_can_update(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)->get(route('task-checklists.edit', $task))->assertOk();

        $this->actingAs($this->owner)
            ->put(route('task-checklists.update', $task), $this->validInput(['title' => '更新後', 'status' => 'done']))
            ->assertRedirect(route('task-checklists.show', $task));

        $this->assertDatabaseHas('task_checklists', ['id' => $task->id, 'title' => '更新後', 'status' => 'done']);
    }

    public function test_updating_image_replaces_old_file(): void
    {
        Storage::fake('public');
        $old = UploadedFile::fake()->image('old.jpg')->store('task-checklists', 'public');
        $task = TaskChecklist::factory()->for($this->owner)->create(['image_path' => $old]);

        $this->actingAs($this->owner)->put(route('task-checklists.update', $task), $this->validInput([
            'image' => UploadedFile::fake()->image('new.png'),
        ]));

        $task->refresh();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($task->image_path);
    }

    public function test_non_owner_cannot_edit_or_update(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create(['title' => '元のタイトル']);

        foreach ([$this->teammate, $this->outsider] as $user) {
            $this->actingAs($user)->get(route('task-checklists.edit', $task))->assertForbidden();
            $this->actingAs($user)
                ->put(route('task-checklists.update', $task), $this->validInput(['title' => '書き換え']))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('task_checklists', ['id' => $task->id, 'title' => '元のタイトル']);
    }

    public function test_edit_and_delete_buttons_are_shown_only_to_owner(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create();
        $editUrl = route('task-checklists.edit', $task);

        $this->actingAs($this->owner)->get(route('task-checklists.show', $task))->assertSee($editUrl, false);
        $this->actingAs($this->teammate)->get(route('task-checklists.show', $task))->assertDontSee($editUrl, false);
    }

    // ───────── 削除（F-08・F-15） ─────────

    public function test_owner_can_delete_and_is_redirected_to_my_list(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('a.jpg')->store('task-checklists', 'public');
        $task = TaskChecklist::factory()->for($this->owner)->create(['image_path' => $path]);

        $this->actingAs($this->owner)
            ->delete(route('task-checklists.destroy', $task))
            ->assertRedirect(route('task-checklists.mine'));

        $this->assertModelMissing($task);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_owner_cannot_delete(): void
    {
        $task = TaskChecklist::factory()->for($this->owner)->create();

        $this->actingAs($this->teammate)->delete(route('task-checklists.destroy', $task))->assertForbidden();
        $this->actingAs($this->outsider)->delete(route('task-checklists.destroy', $task))->assertForbidden();

        $this->assertModelExists($task);
    }
}
