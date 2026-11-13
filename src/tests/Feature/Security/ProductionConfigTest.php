<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * セキュリティ：.env が Git に含まれていない／本番で APP_DEBUG=false
 *
 * 本番の APP_DEBUG の値そのものは Render の環境変数で決まるため、テストでは確かめられない。
 * ここでは「APP_DEBUG=false のときにエラーの中身が画面に出ないこと」と、
 * リポジトリに秘密情報が入らない設定になっていることを確かめる。
 */
class ProductionConfigTest extends TestCase
{
    public function test_error_details_are_hidden_when_debug_is_false(): void
    {
        config(['app.debug' => false]);
        Route::get('/__test-error', fn () => throw new RuntimeException('SECRET_DB_PASSWORD=hunter2'));

        $this->get('/__test-error')
            ->assertStatus(500)
            ->assertDontSee('SECRET_DB_PASSWORD')
            ->assertDontSee('RuntimeException')
            ->assertDontSee(base_path());
    }

    public function test_gitignore_excludes_env_files(): void
    {
        $lines = array_map('trim', file(base_path('.gitignore')));

        $this->assertContains('.env', $lines);
        $this->assertContains('.env.production', $lines);
    }

    public function test_env_example_has_no_app_key(): void
    {
        // .env.example は Git に入るので、本物の APP_KEY を書いてはいけない
        $this->assertMatchesRegularExpression('/^APP_KEY=\s*$/m', file_get_contents(base_path('.env.example')));
    }
}
