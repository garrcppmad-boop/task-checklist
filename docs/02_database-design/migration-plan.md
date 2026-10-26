# マイグレーション計画：チーム業務共有ボード

> 設計概要（[00_overview.md](../00_overview.md)）とテーブル定義（[table-definitions.md](./table-definitions.md)）をもとに、
> マイグレーションを作る順番・内容・初期データ（Seeder）をまとめたもの。

## 1. 作る順番

外部キーで参照されるテーブル（親）を先に作り、参照するテーブル（子）を後に作る。

```
teams ──▶ users ──▶ tasks ──▶ comments
                       └────▶ bookmarks
```

| 順番 | ファイル名 | 内容 | 参照する（親）テーブル | 作成者 |
|---|---|---|---|---|
| 1 | `0001_01_01_000000_create_users_table.php` | users・password_reset_tokens・sessions | ― | Laravel（初期状態） |
| 2 | `0001_01_01_000001_create_cache_table.php` | cache・cache_locks | ― | Laravel（初期状態） |
| 3 | `0001_01_01_000002_create_jobs_table.php` | jobs・job_batches・failed_jobs | ― | Laravel（初期状態） |
| 4 | `YYYY_MM_DD_000001_create_teams_table.php` | teams を作成 | ― | 自作 |
| 5 | `YYYY_MM_DD_000002_add_team_id_to_users_table.php` | users に team_id を追加 | teams | 自作 |
| 6 | `YYYY_MM_DD_000003_create_tasks_table.php` | tasks を作成 | users | 自作 |
| 7 | `YYYY_MM_DD_000004_create_comments_table.php` | comments を作成 | tasks・users | 自作 |
| 8 | `YYYY_MM_DD_000005_create_bookmarks_table.php` | bookmarks を作成 | users・tasks | 自作 |

### users の team_id を別のマイグレーションで追加する理由
- Laravel の初期状態の users マイグレーション（1）は、teams（4）より先に実行される
- users の作成時に team_id の外部キーを付けると、まだ teams が存在しないためエラーになる
- そのため、teams を作った後に「users に team_id を追加する」マイグレーション（5）を実行する
- Laravel 12 では、テストで使う SQLite でも NOT NULL の外部キー列を後から追加できることを確認済み

### ファイル名のルール
- 必ず `php artisan make:migration` で作り、日時のファイル名にする（作った順に実行される）
- 手で `xxxx_create_...` のような名前を付けない。マイグレーションはファイル名順に実行されるため、
  日時でない名前は日時のファイルより後に並び、参照先のテーブルがまだ無い・順番が狂うといった問題が起きる

```bash
php artisan make:migration create_teams_table
php artisan make:migration add_team_id_to_users_table --table=users
php artisan make:migration create_tasks_table
php artisan make:migration create_comments_table
php artisan make:migration create_bookmarks_table
```

---

## 2. 各マイグレーションの内容

### 4. create_teams_table

```php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();
    $table->timestamps();
});
```
- down: `Schema::dropIfExists('teams');`

### 5. add_team_id_to_users_table

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('team_id')->after('id')->constrained()->restrictOnDelete();
});
```
- メンバーがいるチームは削除できない（RESTRICT）
- down:
  ```php
  Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('team_id');
  });
  ```

### 6. create_tasks_table

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title', 100);
    $table->text('description')->nullable();
    $table->string('image_path')->nullable();
    $table->date('due_date')->index();
    $table->string('status', 20)->default('todo')->index(); // todo / doing / done
    $table->timestamps();
});
```
- `due_date`：一覧の期限順の並び替え、期限切れ・期限間近の絞り込みに使う
- `status`：ステータスでの絞り込みに使う
- down: `Schema::dropIfExists('tasks');`

### 7. create_comments_table

```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('body', 500);
    $table->timestamps();

    $table->index(['task_id', 'created_at']); // 詳細画面でコメントを古い順に表示
});
```
- 業務を削除すると、そのコメントも削除される（CASCADE）
- down: `Schema::dropIfExists('comments');`

### 8. create_bookmarks_table

```php
Schema::create('bookmarks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('task_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'task_id']); // 同じ業務を二重にブックマークできない
});
```
- `unique(['user_id', 'task_id'])` は user_id で始まるインデックスも兼ねる。task_id 側は `constrained()` で自動的にインデックスが作られる
- down: `Schema::dropIfExists('bookmarks');`

---

## 3. 初期データ（Seeder・Factory）

| ファイル | 内容 | 本番で実行 |
|---|---|---|
| `TeamSeeder` | 3チーム（第1営業・第2営業・営業事務）を登録 | **する**（会員登録でチームを選ぶため必須） |
| `DemoSeeder` | 動作確認用のユーザー・業務・コメント・ブックマーク | しない（開発環境だけ） |
| `DatabaseSeeder` | 上の2つを呼び出す | ― |

| Factory | 内容 |
|---|---|
| `TeamFactory` | チーム名 |
| `UserFactory` | Breeze の初期状態に team_id を追加 |
| `TaskFactory` | 期限・ステータスをランダムに。`overdue()` `dueSoon()` `done()` の状態を用意（色分けのテスト用） |
| `CommentFactory` | 業務・ユーザー・本文 |

### DemoSeeder で用意するデータ
- 各チームに3人ずつ（ログイン確認用に `demo1@example.com` ／ パスワード `password` など）
- 色分けを確認できるよう、期限切れ・期限間近（3日以内）・通常・完了の業務を、それぞれ数件ずつ
- 一覧のページネーション（20件）を確認できるよう、1チームに25件以上の業務
- テストデータに実在の会社名・個人情報は使わない（要件定義書「6. 制約条件」）

---

## 4. 実行方法

```bash
# 開発環境：作り直してデモデータまで入れる
php artisan migrate:fresh --seed

# 本番環境：未実行のマイグレーションだけ実行し、チームを登録
php artisan migrate --force
php artisan db:seed --class=TeamSeeder --force
```

- `migrate:fresh` は全テーブルを削除するので、**本番では絶対に使わない**
- テスト（PHPUnit）は `RefreshDatabase` で、SQLite のメモリ上の DB に毎回マイグレーションを実行する

## 5. 作業の進め方

| 時期 | 作業 | 確認すること |
|---|---|---|
| 1週目（DB 構築） | 1〜8 のマイグレーション、モデルとリレーション、Factory、Seeder | `migrate:fresh --seed` がエラーなく終わる。phpMyAdmin で外部キー・インデックスを確認 |
| 1週目 | `migrate:rollback` で全部戻せるか | down の処理が正しく、`migrate:fresh` を繰り返しても壊れない |
| 開発中に変更が必要になったとき | すでに実行済みのマイグレーションは書き換えず、変更用のマイグレーションを新しく作る | 本番の DB と開発の DB で、実行済みのマイグレーションが食い違わない |

## 6. 未決事項による変更の可能性

| 未決事項（00_overview.md） | 決まった場合のマイグレーション |
|---|---|
| 削除した業務を戻せるようにする | tasks に `softDeletes()`（deleted_at）を追加するマイグレーションを作る |
| 他のチームの業務も見られるようにする | テーブルの変更は不要（Policy の変更だけ） |
