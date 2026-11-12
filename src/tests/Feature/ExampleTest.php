<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** トップページ（No.1）：未ログインならログイン画面へ、ログイン済みならチームの業務一覧へ */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_page_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_top_page_redirects_users_to_team_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('task-checklists.index'));
    }
}
