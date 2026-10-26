# DB設計：チーム業務共有ボード

> 要件定義書（`docs/requirements.md`）の「3-3. 主なデータ」をもとにしたテーブル定義。
> テーブル同士の関係（ER 図）は [er-diagram.md](./er-diagram.md) を参照。

---

## テーブル: teams（チーム）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|--------------|-------------|------|-----------|-------------------|
| id | BIGINT | NO | AUTO | チームID（PK） |
| name | VARCHAR(50) | NO | - | チーム名（例：第1営業） |
| created_at | TIMESTAMP | NO | CURRENT | 作成日時 |
| updated_at | TIMESTAMP | NO | CURRENT | 更新日時 |

【インデックス】
- PRIMARY KEY (id)
- UNIQUE KEY (name)

【制約】
- name: UNIQUE制約
- データは Seeder であらかじめ登録する（画面からの作成・編集は範囲外）

---

## テーブル: users（ユーザー）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|--------------|-------------|------|-----------|-------------------|
| id | BIGINT | NO | AUTO | ユーザーID（PK） |
| team_id | BIGINT | NO | - | 所属チームID（FK） |
| name | VARCHAR(50) | NO | - | ユーザー名 |
| email | VARCHAR(100) | NO | - | メールアドレス |
| email_verified_at | TIMESTAMP | YES | NULL | メール確認日時（Breeze） |
| password | VARCHAR(255) | NO | - | パスワード（ハッシュ） |
| remember_token | VARCHAR(100) | YES | NULL | ログイン状態を保持するトークン（Breeze） |
| created_at | TIMESTAMP | NO | CURRENT | 作成日時 |
| updated_at | TIMESTAMP | NO | CURRENT | 更新日時 |

【インデックス】
- PRIMARY KEY (id)
- UNIQUE KEY (email)
- INDEX (team_id)

【外部キー】
- team_id REFERENCES teams(id) ON DELETE RESTRICT（メンバーがいるチームは削除できない）

【制約】
- email: UNIQUE制約
- password: 最低8文字（bcrypt でハッシュ化して保存）

---

## テーブル: tasks（業務）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|--------------|-------------|------|-----------|-------------------|
| id | BIGINT | NO | AUTO | 業務ID（PK） |
| user_id | BIGINT | NO | - | 投稿者のユーザーID（FK） |
| title | VARCHAR(100) | NO | - | タイトル |
| description | TEXT | YES | NULL | 説明 |
| image_path | VARCHAR(255) | YES | NULL | 添付画像の保存パス |
| due_date | DATE | NO | - | 期限 |
| status | VARCHAR(20) | NO | 'todo' | ステータス（todo：未着手／doing：進行中／done：完了） |
| created_at | TIMESTAMP | NO | CURRENT | 作成日時 |
| updated_at | TIMESTAMP | NO | CURRENT | 更新日時 |

【インデックス】
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (due_date)（一覧の期限順の並び替え、期限切れ・期限間近の絞り込み）
- INDEX (status)（ステータスでの絞り込み）

【外部キー】
- user_id REFERENCES users(id) ON DELETE CASCADE

【制約】
- status: 'todo' / 'doing' / 'done' のいずれか（アプリ側のバリデーションで確認）
- image_path: jpg / png / webp、2MB まで（アップロード時のバリデーション）

---

## テーブル: comments（コメント）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|--------------|-------------|------|-----------|-------------------|
| id | BIGINT | NO | AUTO | コメントID（PK） |
| task_id | BIGINT | NO | - | 業務ID（FK） |
| user_id | BIGINT | NO | - | コメントしたユーザーID（FK） |
| body | VARCHAR(500) | NO | - | コメント本文 |
| created_at | TIMESTAMP | NO | CURRENT | 作成日時 |
| updated_at | TIMESTAMP | NO | CURRENT | 更新日時 |

【インデックス】
- PRIMARY KEY (id)
- INDEX (task_id, created_at)（業務の詳細でコメントを古い順に表示）
- INDEX (user_id)

【外部キー】
- task_id REFERENCES tasks(id) ON DELETE CASCADE（業務を削除するとコメントも削除）
- user_id REFERENCES users(id) ON DELETE CASCADE

---

## テーブル: bookmarks（ブックマーク：users と tasks の中間テーブル）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|--------------|-------------|------|-----------|-------------------|
| id | BIGINT | NO | AUTO | ブックマークID（PK） |
| user_id | BIGINT | NO | - | ユーザーID（FK） |
| task_id | BIGINT | NO | - | 業務ID（FK） |
| created_at | TIMESTAMP | NO | CURRENT | 登録日時 |
| updated_at | TIMESTAMP | NO | CURRENT | 更新日時 |

【インデックス】
- PRIMARY KEY (id)
- UNIQUE KEY (user_id, task_id)
- INDEX (task_id)

【外部キー】
- user_id REFERENCES users(id) ON DELETE CASCADE
- task_id REFERENCES tasks(id) ON DELETE CASCADE（業務を削除するとブックマークも削除）

【制約】
- (user_id, task_id): UNIQUE制約（同じ業務を二重にブックマークできない）

---

## その他のテーブル（Laravel・Breeze が作成）

| テーブル | 用途 |
|---|---|
| password_reset_tokens | パスワードリセット用のトークン（F-03） |
| sessions | ログイン中のセッション |
| cache / cache_locks | キャッシュ |
| jobs / job_batches / failed_jobs | キュー（メール送信など） |
