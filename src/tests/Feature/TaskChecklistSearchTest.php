<?php

namespace Tests\Feature;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** キーワード検索（F-11）・絞り込み（F-12）・ページネーション */
class TaskChecklistSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_keyword_searches_title_and_description(): void
    {
        TaskChecklist::factory()->for($this->user)->create(['title' => '見積書の作成', 'description' => null]);
        TaskChecklist::factory()->for($this->user)->create(['title' => '会議の準備', 'description' => '見積の確認も行う']);
        TaskChecklist::factory()->for($this->user)->create(['title' => '請求書の送付', 'description' => null]);

        $this->actingAs($this->user)->get(route('task-checklists.index', ['keyword' => '見積']))
            ->assertOk()
            ->assertSee('見積書の作成')
            ->assertSee('会議の準備')
            ->assertDontSee('請求書の送付');
    }

    public function test_keyword_with_percent_is_not_a_wildcard(): void
    {
        TaskChecklist::factory()->for($this->user)->create(['title' => '達成率100%の確認']);
        TaskChecklist::factory()->for($this->user)->create(['title' => '別の業務']);

        $this->actingAs($this->user)->get(route('task-checklists.index', ['keyword' => '%']))
            ->assertSee('達成率100%の確認')
            ->assertDontSee('別の業務');
    }

    public function test_keyword_search_does_not_leak_other_teams(): void
    {
        TaskChecklist::factory()->for(User::factory())->create(['title' => '別チームの見積']);

        $this->actingAs($this->user)->get(route('task-checklists.index', ['keyword' => '見積']))
            ->assertDontSee('別チームの見積');
    }

    public function test_filter_overdue_and_due_soon(): void
    {
        TaskChecklist::factory()->for($this->user)->overdue()->create(['title' => '期限切れの業務']);
        TaskChecklist::factory()->for($this->user)->dueSoon()->create(['title' => '期限間近の業務']);
        TaskChecklist::factory()->for($this->user)->create(['title' => '余裕のある業務']);
        TaskChecklist::factory()->for($this->user)->overdue()->done()->create(['title' => '完了した業務']);

        $this->actingAs($this->user)->get(route('task-checklists.index', ['filter' => 'overdue']))
            ->assertSee('期限切れの業務')
            ->assertDontSee('期限間近の業務')
            ->assertDontSee('余裕のある業務')
            ->assertDontSee('完了した業務'); // 完了は期限切れに含めない

        $this->actingAs($this->user)->get(route('task-checklists.index', ['filter' => 'due_soon']))
            ->assertSee('期限間近の業務')
            ->assertDontSee('期限切れの業務')
            ->assertDontSee('余裕のある業務');
    }

    public function test_filter_by_status(): void
    {
        TaskChecklist::factory()->for($this->user)->create(['title' => '進行中の業務', 'status' => 'doing']);
        TaskChecklist::factory()->for($this->user)->create(['title' => '未着手の業務', 'status' => 'todo']);

        $this->actingAs($this->user)->get(route('task-checklists.index', ['status' => 'doing']))
            ->assertSee('進行中の業務')
            ->assertDontSee('未着手の業務');
    }

    public function test_list_is_paginated_by_20(): void
    {
        TaskChecklist::factory()->count(21)->for($this->user)->create();

        $response = $this->actingAs($this->user)->get(route('task-checklists.index'));

        $this->assertCount(20, $response->viewData('taskChecklists'));
        $this->assertSame(21, $response->viewData('taskChecklists')->total());
    }
}
