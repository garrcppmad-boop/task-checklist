<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * チェックリスト：検索・フィルタが期待通りに動作するか（F-11・F-12）
 * 日付で結果が変わらないよう、今日を 2026-11-13 に固定する
 */
class SearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-13 10:00:00');
        $this->user = User::factory()->create();
    }

    private function task(array $attributes): TaskChecklist
    {
        return TaskChecklist::factory()->for($this->user)->create($attributes);
    }

    private function index(array $query = [])
    {
        return $this->actingAs($this->user)->get(route('task-checklists.index', $query))->assertOk();
    }

    // ───────── キーワード検索（F-11） ─────────

    public function test_keyword_matches_title(): void
    {
        $this->task(['title' => 'A社の見積書', 'description' => null]);
        $this->task(['title' => '議事録', 'description' => null]);

        $this->index(['keyword' => '見積'])
            ->assertSee('A社の見積書')
            ->assertDontSee('議事録');
    }

    public function test_keyword_matches_description(): void
    {
        $this->task(['title' => '業務X', 'description' => '見積の確認をする']);
        $this->task(['title' => '業務Y', 'description' => '別の作業']);

        $this->index(['keyword' => '見積'])
            ->assertSee('業務X')
            ->assertDontSee('業務Y');
    }

    public function test_keyword_does_not_leak_other_team_task_checklists(): void
    {
        // orWhere がかっこでくくられていないと、他チームの業務が説明の一致で出てしまう
        $outsider = User::factory()->create();
        TaskChecklist::factory()->for($outsider)->create(['title' => '他チームの業務', 'description' => '見積']);

        $this->index(['keyword' => '見積'])->assertDontSee('他チームの業務');
    }

    public function test_percent_and_underscore_are_treated_literally(): void
    {
        $this->task(['title' => '達成率100%', 'description' => null]);
        $this->task(['title' => '普通の業務', 'description' => null]);

        $this->index(['keyword' => '%'])
            ->assertSee('達成率100%')
            ->assertDontSee('普通の業務');

        $this->index(['keyword' => '_'])
            ->assertSee('条件に合う業務がありません');
    }

    public function test_blank_keyword_shows_all(): void
    {
        $this->task(['title' => '業務A']);
        $this->task(['title' => '業務B']);

        $this->index(['keyword' => '   '])
            ->assertSee('業務A')
            ->assertSee('業務B');
    }

    public function test_no_result_message_is_displayed(): void
    {
        $this->task(['title' => '業務A']);

        $this->index(['keyword' => '存在しない言葉'])
            ->assertSee('条件に合う業務がありません');
    }

    // ───────── 期限での絞り込み（F-12） ─────────

    public function test_overdue_filter(): void
    {
        $this->task(['title' => '昨日が期限', 'due_date' => '2026-11-12', 'status' => 'todo']);
        $this->task(['title' => '今日が期限', 'due_date' => '2026-11-13', 'status' => 'todo']);
        $this->task(['title' => '昨日が期限で完了', 'due_date' => '2026-11-12', 'status' => 'done']);

        $this->index(['filter' => 'overdue'])
            ->assertSee('昨日が期限')
            ->assertDontSee('今日が期限')
            ->assertDontSee('昨日が期限で完了');
    }

    public function test_due_soon_filter_boundaries(): void
    {
        $this->task(['title' => '期限_今日', 'due_date' => '2026-11-13']);
        $this->task(['title' => '期限_3日後', 'due_date' => '2026-11-16']);
        $this->task(['title' => '期限_4日後', 'due_date' => '2026-11-17']);
        $this->task(['title' => '期限_昨日', 'due_date' => '2026-11-12']);
        $this->task(['title' => '期限_2日後_完了', 'due_date' => '2026-11-15', 'status' => 'done']);

        $this->index(['filter' => 'due_soon'])
            ->assertSee('期限_今日')
            ->assertSee('期限_3日後')
            ->assertDontSee('期限_4日後')
            ->assertDontSee('期限_昨日')
            ->assertDontSee('期限_2日後_完了');
    }

    // ───────── ステータスでの絞り込み（F-12） ─────────

    public function test_status_filter(): void
    {
        $this->task(['title' => '未着手の業務', 'status' => 'todo']);
        $this->task(['title' => '進行中の業務', 'status' => 'doing']);
        $this->task(['title' => '完了の業務', 'status' => 'done']);

        $this->index(['status' => 'doing'])
            ->assertSee('進行中の業務')
            ->assertDontSee('未着手の業務')
            ->assertDontSee('完了の業務');
    }

    public function test_unknown_filter_and_status_are_ignored(): void
    {
        $this->task(['title' => '業務A']);

        $this->index(['filter' => 'unknown', 'status' => 'unknown'])->assertSee('業務A');
    }

    public function test_conditions_can_be_combined(): void
    {
        $this->task(['title' => '見積_進行中_間近', 'due_date' => '2026-11-14', 'status' => 'doing']);
        $this->task(['title' => '見積_未着手_間近', 'due_date' => '2026-11-14', 'status' => 'todo']);
        $this->task(['title' => '見積_進行中_先', 'due_date' => '2026-12-31', 'status' => 'doing']);
        $this->task(['title' => '議事録_進行中_間近', 'due_date' => '2026-11-14', 'status' => 'doing', 'description' => null]);

        $this->index(['keyword' => '見積', 'filter' => 'due_soon', 'status' => 'doing'])
            ->assertSee('見積_進行中_間近')
            ->assertDontSee('見積_未着手_間近')
            ->assertDontSee('見積_進行中_先')
            ->assertDontSee('議事録_進行中_間近');
    }

    public function test_search_form_keeps_entered_values(): void
    {
        $this->index(['keyword' => '見積', 'filter' => 'overdue', 'status' => 'done'])
            ->assertSee('value="見積"', false)
            ->assertSee('<option value="overdue" selected', false)
            ->assertSee('<option value="done" selected', false);
    }

    // ───────── 不正なパラメータで 500 にならない ─────────

    public function test_array_parameters_do_not_cause_server_error(): void
    {
        $this->task(['title' => '業務A']);

        $this->actingAs($this->user)
            ->get(route('task-checklists.index').'?keyword[]=a&filter[]=overdue&status[]=todo')
            ->assertOk();
    }

    // ───────── 期限の色分け（F-13） ─────────

    public function test_deadline_marks_are_displayed(): void
    {
        $this->task(['title' => '期限切れ業務', 'due_date' => '2026-11-12']);
        $this->task(['title' => '間近業務', 'due_date' => '2026-11-15']);
        $this->task(['title' => '完了業務', 'due_date' => '2026-11-12', 'status' => 'done']);

        $this->index()
            ->assertSee('⚠ 2026/11/12（期限切れ）', false)
            ->assertSee('⏰ 2026/11/15（期限間近）', false)
            ->assertSee('✓ 2026/11/12', false);
    }
}
