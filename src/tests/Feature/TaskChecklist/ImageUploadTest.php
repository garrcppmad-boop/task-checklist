<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use App\Services\TaskChecklistImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * チェックリスト：画像アップロードが正常に動作するか（F-09）
 * Storage::fake() で本物のストレージには書き込まない
 */
class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(TaskChecklistImageService::DISK);
        $this->user = User::factory()->create();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'title' => '画像つきの業務',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
        ], $overrides);
    }

    public function test_image_is_stored_with_task_checklist(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $task = TaskChecklist::sole();
        $this->assertNotNull($task->image_path);
        $this->assertStringStartsWith(TaskChecklistImageService::DIRECTORY.'/', $task->image_path);
        $this->assertStringNotContainsString('photo', $task->image_path); // 元のファイル名は使わない
        Storage::disk(TaskChecklistImageService::DISK)->assertExists($task->image_path);
    }

    public function test_task_checklist_can_be_stored_without_image(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data())
            ->assertSessionHasNoErrors();

        $this->assertNull(TaskChecklist::sole()->image_path);
    }

    public function test_uploaded_image_is_displayed_on_show_page(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->image('photo.png'),
            ]));

        $task = TaskChecklist::sole();

        $this->get(route('task-checklists.show', $task))
            ->assertOk()
            ->assertSee($task->image_url);
    }

    public function test_png_and_webp_are_accepted(): void
    {
        foreach (['a.png', 'b.webp'] as $name) {
            $this->actingAs($this->user)
                ->post(route('task-checklists.store'), $this->data([
                    'image' => UploadedFile::fake()->image($name),
                ]))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('task_checklists', 2);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('task_checklists', 0);
        $this->assertEmpty(Storage::disk(TaskChecklistImageService::DISK)->allFiles());
    }

    public function test_gif_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->image('anim.gif'),
            ]))
            ->assertSessionHasErrors('image');
    }

    public function test_image_larger_than_2mb_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->image('big.jpg')->size(2049),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_image_of_exactly_2mb_is_accepted(): void
    {
        $this->actingAs($this->user)
            ->post(route('task-checklists.store'), $this->data([
                'image' => UploadedFile::fake()->image('ok.jpg')->size(2048),
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_updating_with_new_image_replaces_old_image(): void
    {
        $disk = Storage::disk(TaskChecklistImageService::DISK);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store(TaskChecklistImageService::DIRECTORY, TaskChecklistImageService::DISK);
        $task = TaskChecklist::factory()->for($this->user)->create(['image_path' => $oldPath]);

        $this->actingAs($this->user)
            ->put(route('task-checklists.update', $task), $this->data([
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $newPath = $task->fresh()->image_path;
        $this->assertNotSame($oldPath, $newPath);
        $disk->assertExists($newPath);
        $disk->assertMissing($oldPath);
    }

    public function test_updating_without_image_keeps_old_image(): void
    {
        $disk = Storage::disk(TaskChecklistImageService::DISK);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store(TaskChecklistImageService::DIRECTORY, TaskChecklistImageService::DISK);
        $task = TaskChecklist::factory()->for($this->user)->create(['image_path' => $oldPath]);

        $this->actingAs($this->user)
            ->put(route('task-checklists.update', $task), $this->data(['title' => 'タイトルだけ変更']))
            ->assertSessionHasNoErrors();

        $this->assertSame($oldPath, $task->fresh()->image_path);
        $disk->assertExists($oldPath);
    }

    public function test_invalid_image_on_update_keeps_old_image(): void
    {
        $disk = Storage::disk(TaskChecklistImageService::DISK);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store(TaskChecklistImageService::DIRECTORY, TaskChecklistImageService::DISK);
        $task = TaskChecklist::factory()->for($this->user)->create(['image_path' => $oldPath]);

        $this->actingAs($this->user)
            ->put(route('task-checklists.update', $task), $this->data([
                'image' => UploadedFile::fake()->create('virus.exe', 10),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertSame($oldPath, $task->fresh()->image_path);
        $disk->assertExists($oldPath);
    }

    public function test_deleting_task_checklist_deletes_image(): void
    {
        $disk = Storage::disk(TaskChecklistImageService::DISK);
        $path = UploadedFile::fake()->image('old.jpg')->store(TaskChecklistImageService::DIRECTORY, TaskChecklistImageService::DISK);
        $task = TaskChecklist::factory()->for($this->user)->create(['image_path' => $path]);

        $this->actingAs($this->user)->delete(route('task-checklists.destroy', $task));

        $disk->assertMissing($path);
    }

    public function test_forbidden_update_does_not_store_image(): void
    {
        $other = User::factory()->create(['team_id' => $this->user->team_id]);
        $task = TaskChecklist::factory()->for($other)->create();

        $this->actingAs($this->user)
            ->put(route('task-checklists.update', $task), $this->data([
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertForbidden();

        $this->assertEmpty(Storage::disk(TaskChecklistImageService::DISK)->allFiles());
    }
}
