<?php

namespace Tests\Feature\Security;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * セキュリティ：認証・認可が適切か（業務ごとの権限は tests/Feature/TaskChecklist/AuthorizationTest）
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_stored_hashed(): void
    {
        $team = Team::factory()->create();

        $this->post('/register', [
            'team_id' => $team->id,
            'name' => 'Hash User',
            'email' => 'hash@example.com',
            'password' => 'plain-password-123',
            'password_confirmation' => 'plain-password-123',
        ]);

        $stored = User::where('email', 'hash@example.com')->value('password');
        $this->assertNotSame('plain-password-123', $stored);
        $this->assertTrue(Hash::check('plain-password-123', $stored));
    }

    public function test_login_is_throttled_after_5_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // 6回目は正しいパスワードでもログインできない（総当たり対策）
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_wrong_password_does_not_reveal_whether_email_exists(): void
    {
        $user = User::factory()->create();

        $existing = $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->getSession()->get('errors')->first('email');
        $missing = $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->getSession()->get('errors')->first('email');

        $this->assertSame($existing, $missing);
    }

    public function test_session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticated();
        $this->assertNotSame($before, session()->getId()); // セッション固定攻撃の対策
    }

    public function test_user_cannot_move_to_another_team_via_profile_update(): void
    {
        // チームを書き換えられると、他のチームの業務が見えてしまう
        $user = User::factory()->create();
        $otherTeam = Team::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'team_id' => $otherTeam->id,
        ]);

        $this->assertNotSame($otherTeam->id, $user->fresh()->team_id);
    }

    public function test_registration_cannot_set_arbitrary_fields(): void
    {
        $team = Team::factory()->create();

        $this->post('/register', [
            'team_id' => $team->id,
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'id' => 9999,
            'email_verified_at' => '2000-01-01 00:00:00',
        ]);

        $user = User::where('email', 'attacker@example.com')->sole();
        $this->assertNotSame(9999, $user->id);
        $this->assertNull($user->email_verified_at);
    }

    public function test_password_is_not_exposed_in_serialized_user(): void
    {
        $array = User::factory()->create()->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
