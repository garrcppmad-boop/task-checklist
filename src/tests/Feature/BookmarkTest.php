<?php

namespace Tests\Feature;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ブックマークの登録・解除（F-14・Ajax） */
class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_teammate_can_toggle_bookmark_and_gets_json(): void
    {
        $owner = User::factory()->create();
        $teammate = User::factory()->for($owner->team)->create();
        $task = TaskChecklist::factory()->for($owner)->create();
        $url = route('task-checklists.bookmark', $task);

        $this->actingAs($teammate)->postJson($url)->assertOk()->assertExactJson(['bookmarked' => true]);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $teammate->id, 'task_checklist_id' => $task->id]);

        $this->actingAs($teammate)->postJson($url)->assertOk()->assertExactJson(['bookmarked' => false]);
        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_outsider_cannot_bookmark(): void
    {
        $task = TaskChecklist::factory()->create();
        $outsider = User::factory()->create(); // 別のチーム

        $this->actingAs($outsider)
            ->postJson(route('task-checklists.bookmark', $task))
            ->assertForbidden();

        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_guest_cannot_bookmark(): void
    {
        $task = TaskChecklist::factory()->create();

        $this->postJson(route('task-checklists.bookmark', $task))->assertUnauthorized();
    }
}
