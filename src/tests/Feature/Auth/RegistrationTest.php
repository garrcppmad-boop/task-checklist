<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 会員登録（F-01）：名前・メールアドレス・パスワード・所属チームを入力して登録できる */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        Team::factory()->create(['name' => '営業チーム']);

        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('name="team_id"', false)
            ->assertSee('営業チーム');
    }

    public function test_new_users_can_register(): void
    {
        $team = Team::factory()->create();

        $response = $this->post('/register', [
            'team_id' => $team->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($team->id, User::sole()->team_id);
    }

    public function test_team_is_required(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('team_id');

        $this->assertGuest();
    }

    public function test_team_must_exist(): void
    {
        $this->post('/register', [
            'team_id' => 99999,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('team_id');

        $this->assertGuest();
    }
}
