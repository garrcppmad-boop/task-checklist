<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskChecklistController;
use Illuminate\Support\Facades\Route;

// トップページ：ログイン済みならチームの業務一覧へ、未ログインならログイン画面へ
Route::get('/', fn () => redirect()->route(auth()->check() ? 'task-checklists.index' : 'login'))
    ->name('home');

// Breeze のログイン後・会員登録後の移動先（route('dashboard')）を、チームの業務一覧に向ける
Route::get('/dashboard', fn () => redirect()->route('task-checklists.index'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // 業務（/task-checklists/mine は /task-checklists/{task_checklist} より前に書く）
    Route::get('/task-checklists/mine', [TaskChecklistController::class, 'mine'])->name('task-checklists.mine');
    Route::resource('task-checklists', TaskChecklistController::class);

    // プロフィール（Breeze）
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
