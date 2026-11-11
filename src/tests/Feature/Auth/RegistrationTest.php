<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $team = Team::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'team_id' => $team->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'team_id' => $team->id]);
    }

    /** 所属チームを選ばないと登録できない（F-01） */
    public function test_team_is_required_to_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('team_id');
        $this->assertGuest();
    }

    /** 登録画面に、Seeder で用意したチームが選択肢として出る */
    public function test_registration_screen_lists_teams(): void
    {
        $team = Team::factory()->create(['name' => '第1営業']);

        $this->get('/register')->assertOk()->assertSee('第1営業');
    }
}
