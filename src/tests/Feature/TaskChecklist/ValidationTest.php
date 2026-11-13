<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * チェックリスト：バリデーションエラーが適切に表示されるか
 * エラーメッセージは言語ファイルから組み立てて比較する（日本語化してもテストが壊れないように）
 */
class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => '正しいタイトル',
            'description' => '',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
        ], $overrides);
    }

    public static function invalidInputs(): array
    {
        return [
            'タイトルなし' => [['title' => ''], 'title'],
            'タイトル101文字' => [['title' => str_repeat('あ', 101)], 'title'],
            '期限なし' => [['due_date' => ''], 'due_date'],
            '期限が日付でない' => [['due_date' => 'あした'], 'due_date'],
            'ステータスなし' => [['status' => ''], 'status'],
            'ステータスが不正' => [['status' => 'archived'], 'status'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_store_rejects_invalid_input(array $input, string $field): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('task-checklists.create'))
            ->post(route('task-checklists.store'), $this->validData($input))
            ->assertRedirect(route('task-checklists.create'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('task_checklists', 0);
    }

    #[DataProvider('invalidInputs')]
    public function test_update_rejects_invalid_input(array $input, string $field): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create(['title' => '元のタイトル']);

        $this->actingAs($user)
            ->from(route('task-checklists.edit', $task))
            ->put(route('task-checklists.update', $task), $this->validData($input))
            ->assertRedirect(route('task-checklists.edit', $task))
            ->assertSessionHasErrors($field);

        $this->assertSame('元のタイトル', $task->fresh()->title);
    }

    public function test_title_with_100_characters_is_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('task-checklists.store'), $this->validData(['title' => str_repeat('あ', 100)]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('task_checklists', 1);
    }

    public function test_error_messages_and_old_input_are_displayed_on_create_form(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('task-checklists.create'))
            ->followingRedirects()
            ->post(route('task-checklists.store'), [
                'title' => '',
                'description' => '入力した説明',
                'due_date' => '',
                'status' => 'todo',
            ]);

        $response->assertOk()
            ->assertSee(__('validation.required', ['attribute' => 'タイトル']))
            ->assertSee(__('validation.required', ['attribute' => '期限']))
            ->assertSee('入力した説明'); // 入力した値が消えない（old()）
    }

    public function test_error_messages_are_displayed_on_edit_form(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('task-checklists.edit', $task))
            ->followingRedirects()
            ->put(route('task-checklists.update', $task), $this->validData(['title' => '']))
            ->assertOk()
            ->assertSee(__('validation.required', ['attribute' => 'タイトル']));
    }
}
