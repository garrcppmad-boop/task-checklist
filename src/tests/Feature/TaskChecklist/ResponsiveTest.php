<?php

namespace Tests\Feature\TaskChecklist;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * チェックリスト：レスポンシブ対応ができているか（最低限の確認）
 *
 * Feature Test は CSS を描画しないため、見た目の崩れまでは確認できない。
 * ここでは「スマホ表示の前提となる HTML が出ているか」だけを確かめる。
 * 実際の見た目は、ブラウザの開発者ツール（幅 375px など）で目視するか、Laravel Dusk で確認する。
 */
class ResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_have_viewport_meta_and_mobile_menu(): void
    {
        $user = User::factory()->create();
        $task = TaskChecklist::factory()->for($user)->create();

        $urls = [
            route('task-checklists.index'),
            route('task-checklists.mine'),
            route('task-checklists.create'),
            route('task-checklists.show', $task),
            route('task-checklists.edit', $task),
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
                ->assertSee('sm:hidden', false); // スマホ用のハンバーガーメニュー
        }
    }

    public function test_login_page_has_viewport_meta(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false);
    }

    public function test_list_item_stacks_vertically_on_mobile(): void
    {
        $user = User::factory()->create();
        TaskChecklist::factory()->for($user)->create();

        // スマホでは縦並び（flex-col）、sm 以上で横並び（sm:flex-row）
        $this->actingAs($user)->get(route('task-checklists.index'))
            ->assertSee('flex-col', false)
            ->assertSee('sm:flex-row', false);
    }
}
