<?php

namespace Tests\Feature;

use App\Models\TaskChecklist;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 本番の起動時に実行する php artisan db:seed --force（Dockerfile の CMD）
 * - 会員登録で選ぶチームだけを作り、デモ用のユーザー・業務は作らない
 * - デプロイ（再起動）のたびに実行されても、チームが重複しない
 */
class ProductionSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'production';
    }

    /**
     * Dockerfile の CMD と同じく php artisan db:seed --force を実行する
     * （本番では --force がないと「本当に実行しますか？」と聞かれて止まる）
     */
    private function seedLikeProduction(): void
    {
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])
            ->assertSuccessful();
    }

    public function test_production_seed_creates_only_teams(): void
    {
        $this->seedLikeProduction();

        $this->assertEqualsCanonicalizing(TeamSeeder::NAMES, Team::pluck('name')->all());
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(0, TaskChecklist::count());
    }

    public function test_seeding_twice_does_not_duplicate_teams(): void
    {
        $this->seedLikeProduction();
        $this->seedLikeProduction();

        $this->assertDatabaseCount('teams', count(TeamSeeder::NAMES));
    }

    public function test_existing_teams_and_users_are_kept(): void
    {
        $this->seedLikeProduction();
        $user = User::factory()->for(Team::where('name', TeamSeeder::NAMES[0])->sole())->create();

        $this->seedLikeProduction(); // 再デプロイ

        $this->assertModelExists($user);
        $this->assertSame(TeamSeeder::NAMES[0], $user->fresh()->team->name);
    }

    public function test_registration_lists_seeded_teams(): void
    {
        $this->seedLikeProduction();

        $response = $this->get('/register')->assertOk();

        foreach (TeamSeeder::NAMES as $name) {
            $response->assertSee($name);
        }
    }
}
