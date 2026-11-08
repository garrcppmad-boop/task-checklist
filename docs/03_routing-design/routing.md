# ルーティング設計：チーム業務共有ボード

> 要件定義書（`docs/requirements.md`）の画面一覧・機能一覧をもとに、画面と URL・HTTP メソッドを対応させたもの。

## ルート一覧

| 画面・操作 | メソッド | URL | ルート名 | コントローラー@アクション | ログイン | 権限（Policy） |
|--------------------|-----------|-----------------------|----------------|---------------------------|----------|------------------|
| トップページ | GET | / | home | （クロージャ） | 不要 | - |
| チームの業務一覧（検索・絞り込み） | GET | /task-checklists?keyword=...&filter=... | task-checklists.index | TaskChecklistController@index | 必要 | - |
| 自分の業務一覧 | GET | /task-checklists/mine | task-checklists.mine | TaskChecklistController@mine | 必要 | - |
| 業務の作成フォーム | GET | /task-checklists/create | task-checklists.create | TaskChecklistController@create | 必要 | - |
| 業務の保存 | POST | /task-checklists | task-checklists.store | TaskChecklistController@store | 必要 | - |
| 業務の詳細 | GET | /task-checklists/{task_checklist} | task-checklists.show | TaskChecklistController@show | 必要 | 同じチームの業務だけ |
| 業務の編集フォーム | GET | /task-checklists/{task_checklist}/edit | task-checklists.edit | TaskChecklistController@edit | 必要 | 自分の投稿だけ |
| 業務の更新 | PUT | /task-checklists/{task_checklist} | task-checklists.update | TaskChecklistController@update | 必要 | 自分の投稿だけ |
| 業務の削除 | DELETE | /task-checklists/{task_checklist} | task-checklists.destroy | TaskChecklistController@destroy | 必要 | 自分の投稿だけ |
| ステータスの更新（Ajax） | PATCH | /task-checklists/{task_checklist}/status | task-checklists.status | TaskChecklistStatusController@update | 必要 | 自分の投稿だけ |
| コメントの投稿 | POST | /task-checklists/{task_checklist}/comments | task-checklists.comments.store | CommentController@store | 必要 | 同じチームの業務だけ |
| コメントの削除 | DELETE | /comments/{comment} | comments.destroy | CommentController@destroy | 必要 | 自分のコメントだけ |
| ブックマーク（Ajax） | POST | /task-checklists/{task_checklist}/bookmark | task-checklists.bookmark | BookmarkController@toggle | 必要 | 同じチームの業務だけ |
| ブックマーク一覧 | GET | /bookmarks | bookmarks.index | BookmarkController@index | 必要 | - |
| プロフィール編集 | GET | /profile | profile.edit | ProfileController@edit | 必要 | - |
| プロフィールの更新 | PATCH | /profile | profile.update | ProfileController@update | 必要 | - |
| アカウントの削除 | DELETE | /profile | profile.destroy | ProfileController@destroy | 必要 | - |

※ ログイン・ログアウト・会員登録・パスワードリセットの画面は Breeze が作るルート（`routes/auth.php`）を使う。会員登録には所属チームの選択を追加する（F-01）
※ トップページ（`/`）は、ログイン済みならチームの業務一覧（`/task-checklists`）へ、未ログインならログイン画面へリダイレクトする
※ ステータスの更新（Ajax）とブックマーク（Ajax）は画面を返さず、JSON を返す
　（ステータス：変更後のステータスと表示用の色・マーク／ブックマーク：登録されているかどうか）
※ 「同じチームの業務だけ」：他のチームの業務を開いたり、コメント・ブックマークしようとすると 403 にする（`TaskChecklistPolicy@view`）
※ 「自分の投稿だけ」は `TaskChecklistPolicy@update` / `TaskChecklistPolicy@delete`、「自分のコメントだけ」は `CommentPolicy@delete` で判定する

## 要件定義書との対応

| 画面・操作 | 機能 ID | 優先度 | 画面一覧の No |
|---|---|---|---|
| トップページ | ― | ― | 1 |
| チームの業務一覧（検索・絞り込み） | F-05・F-11・F-12・F-13 | Must（絞り込みは Should） | 5 |
| 自分の業務一覧 | F-06・F-13 | Must | 6 |
| 業務の作成フォーム・保存 | F-04・F-09 | Must | 8 |
| 業務の詳細 | F-07・F-13 | Must | 9 |
| 業務の編集フォーム・更新・削除 | F-08・F-15 | Must | 10 |
| ステータスの更新（Ajax） | F-17 | Could | ― |
| コメントの投稿 | F-10 | Should | 9（詳細画面の中） |
| コメントの削除 | F-18 | Could | 9（詳細画面の中） |
| ブックマーク（Ajax） | F-14 | Must | 5・9（一覧・詳細の中） |
| ブックマーク一覧 | F-16 | Should | 7 |
| プロフィール | ― | ― | 11 |

## 検索・絞り込みのパラメータ（`GET /task-checklists`）

| パラメータ | 値 | 内容 |
|---|---|---|
| keyword | 文字列 | タイトルと説明を部分一致で検索（F-11） |
| filter | `overdue` | 期限切れ（期限を過ぎていて未完了） |
| | `due_soon` | 期限間近（期限まで3日以内で未完了） |
| status | `todo` / `doing` / `done` | 未着手 / 進行中 / 完了（F-12） |
| page | 数字 | ページ番号（1ページ20件） |

例：`/task-checklists?keyword=見積&filter=due_soon&status=doing`

## routes/web.php のイメージ

```php
Route::get('/', fn () => redirect()->route(auth()->check() ? 'task-checklists.index' : 'login'))->name('home');

Route::middleware('auth')->group(function () {
    // 業務（/task-checklists/mine は /task-checklists/{task_checklist} より前に書く）
    Route::get('/task-checklists/mine', [TaskChecklistController::class, 'mine'])->name('task-checklists.mine');
    Route::resource('task-checklists', TaskChecklistController::class);
    Route::patch('/task-checklists/{task_checklist}/status', [TaskChecklistStatusController::class, 'update'])->name('task-checklists.status');

    // コメント
    Route::post('/task-checklists/{task_checklist}/comments', [CommentController::class, 'store'])->name('task-checklists.comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // ブックマーク
    Route::post('/task-checklists/{task_checklist}/bookmark', [BookmarkController::class, 'toggle'])->name('task-checklists.bookmark');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');

    // プロフィール（Breeze）
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
```

**ルートを書く順番の注意**：`/task-checklists/mine` を `Route::resource('task-checklists', ...)` より後に書くと、
`mine` が `/task-checklists/{task_checklist}` の `{task_checklist}` として扱われ、「mine という ID の業務」を探して 404 になる。
固定の URL（`/task-checklists/mine`・`/task-checklists/create`）は、`{task_checklist}` を含むルートより前に書く。
