<?php

namespace Tests\Feature\Security;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * セキュリティ：SQL インジェクション対策
 * 検索などに SQL の断片を入れても、条件が書き換わらない・エラーにならない・テーブルが壊れないことを確かめる
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        TaskChecklist::factory()->for($this->user)->create(['title' => '自分のチームの業務', 'description' => null]);

        // 他のチームの業務：条件が書き換わると一覧に出てしまう
        TaskChecklist::factory()->for(User::factory())->create(['title' => '他チームの秘密の業務']);
    }

    public static function payloads(): array
    {
        return [
            'OR 1=1' => ["' OR '1'='1"],
            'コメントアウト' => ["%' OR 1=1 --"],
            'UNION' => ["' UNION SELECT id, email, password FROM users --"],
            'DROP TABLE' => ["'; DROP TABLE task_checklists; --"],
            'ESCAPE 句の破壊' => ["!' OR 1=1 --"],
        ];
    }

    #[DataProvider('payloads')]
    public function test_keyword_cannot_inject_sql(string $payload): void
    {
        $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['keyword' => $payload]))
            ->assertOk()
            ->assertDontSee('他チームの秘密の業務')
            ->assertDontSee('自分のチームの業務'); // ただの文字列として検索され、一致しない

        $this->assertTrue(Schema::hasTable('task_checklists'));
        $this->assertDatabaseCount('task_checklists', 2);
    }

    #[DataProvider('payloads')]
    public function test_status_and_filter_cannot_inject_sql(string $payload): void
    {
        // 想定外の値は無視され、通常の一覧（自分のチームだけ）になる
        $this->actingAs($this->user)
            ->get(route('task-checklists.index', ['status' => $payload, 'filter' => $payload]))
            ->assertOk()
            ->assertSee('自分のチームの業務')
            ->assertDontSee('他チームの秘密の業務');
    }

    public function test_route_parameter_cannot_inject_sql(): void
    {
        $this->actingAs($this->user)
            ->get('/task-checklists/'.rawurlencode('1 OR 1=1'))
            ->assertNotFound();
    }

    #[DataProvider('payloads')]
    public function test_payload_is_stored_as_plain_text(string $payload): void
    {
        $this->actingAs($this->user)->post(route('task-checklists.store'), [
            'title' => $payload,
            'description' => $payload,
            'due_date' => today()->toDateString(),
            'status' => 'todo',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('task_checklists', ['title' => $payload]);
        $this->assertDatabaseCount('task_checklists', 3);
    }

    public function test_login_email_cannot_inject_sql(): void
    {
        $this->post('/login', [
            'email' => "' OR '1'='1' --",
            'password' => "' OR '1'='1' --",
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
