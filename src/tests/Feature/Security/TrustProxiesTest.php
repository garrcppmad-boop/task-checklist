<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * HTTPS の裏で動くことを Laravel が分かっているか（bootstrap/app.php の trustProxies）
 * Render は HTTPS を受けてから、X-Forwarded-Proto: https を付けて HTTP でコンテナに転送する
 */
class TrustProxiesTest extends TestCase
{
    public function test_urls_are_https_behind_render_proxy(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'task-checklist.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/login');

        $response->assertOk()
            // @vite が出力する CSS・JS の URL
            ->assertSee('https://task-checklist.onrender.com/build/assets/', false)
            ->assertDontSee('http://task-checklist.onrender.com', false)
            // フォームの送信先も https（http だと送信時に警告が出る）
            ->assertSee('action="https://task-checklist.onrender.com/login"', false);
    }

    public function test_redirects_are_https_behind_render_proxy(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'task-checklist.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/')->assertRedirect('https://task-checklist.onrender.com/login');
    }
}
