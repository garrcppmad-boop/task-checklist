<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * チェックリスト：ページネーションが動作するか（1ページ20件）
 */
class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // 期限の近い順に 業務01 〜 業務21 となるように作る
        foreach (range(1, 21) as $i) {
            TaskChecklist::factory()->for($this->user)->create([
                'title' => sprintf('業務%02d', $i),
                'due_date' => today()->addDays($i),
                'status' => 'todo',
            ]);
        }
    }

    public function test_first_page_shows_20_items(): void
    {
        $response = $this->actingAs($this->user)->get(route('task-checklists.index'))->assertOk();

        $this->assertCount(20, $response->viewData('taskChecklists'));
        $response->assertSee('業務01')->assertSee('業務20')->assertDontSee('業務21');
    }

    public function test_second_page_shows_remaining_items(): void
    {
        $response = $this->actingAs($this->user)->get(route('task-checklists.index', ['page' => 2]))->assertOk();

        $this->assertCount(1, $response->viewData('taskChecklists'));
        $response->assertSee('業務21')->assertDontSee('業務01');
    }

    public function test_page_links_are_displayed(): void
    {
        $this->actingAs($this->user)
            ->get(route('task-checklists.index'))
            ->assertSee(route('task-checklists.index', ['page' => 2]), false);
    }

    public function test_search_conditions_are_kept_in_page_links(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['keyword' => '業務', 'status' => 'todo']));

        $nextUrl = $response->viewData('taskChecklists')->nextPageUrl();
        $this->assertStringContainsString('keyword=', $nextUrl);
        $this->assertStringContainsString('status=todo', $nextUrl);
    }

    public function test_page_beyond_last_shows_empty_list(): void
    {
        $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['page' => 99]))
            ->assertOk()
            ->assertSee('まだ業務が投稿されていません');
    }

    public function test_invalid_page_parameter_falls_back_to_first_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['page' => 'abc']))
            ->assertOk()
            ->assertSee('業務01');
    }

    public function test_mine_is_paginated(): void
    {
        $response = $this->actingAs($this->user)->get(route('task-checklists.mine'))->assertOk();

        $this->assertCount(20, $response->viewData('taskChecklists'));
    }
}
