<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 追加: Render のプロキシを信頼する
        // Render は HTTPS を受けてから HTTP でコンテナに転送するため、X-Forwarded-Proto などの
        // ヘッダーを信頼しないと、Laravel が作る CSS・JS などの URL が http:// になってしまう
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
