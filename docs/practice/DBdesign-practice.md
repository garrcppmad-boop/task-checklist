# 練習課題2（必須）: DB設計

ブログシステム（users, posts, comments, tags）のDB設計書です。

## テーブル一覧

| テーブル名 | 論理名 | 概要 |
|-----------|--------|------|
| users     | ユーザー | ブログを利用するユーザー |
| posts     | 投稿 | ユーザーが書いた記事 |
| comments  | コメント | 投稿に対するコメント |
| tags      | タグ | 投稿に付けるタグ |
| post_tags | 投稿タグ | 投稿とタグの多対多を表す中間テーブル |

## リレーション

- users 1 : N posts（1人のユーザーは複数の投稿を持つ）
- users 1 : N comments（1人のユーザーは複数のコメントを書く）
- posts 1 : N comments（1つの投稿に複数のコメントが付く）
- posts N : N tags（post_tags を介して多対多）

---

## テーブル: users（ユーザー）

| カラム名      | 型           | NULL | デフォルト | 説明              |
|--------------|-------------|------|-----------|-------------------|
| id           | BIGINT      | NO   | AUTO      | ユーザーID（PK）   |
| name         | VARCHAR(50) | NO   | -         | ユーザー名         |
| email        | VARCHAR(100)| NO   | -         | メールアドレス     |
| password     | VARCHAR(255)| NO   | -         | パスワード（ハッシュ）|
| created_at   | TIMESTAMP   | NO   | CURRENT   | 作成日時          |
| updated_at   | TIMESTAMP   | NO   | CURRENT   | 更新日時          |

【インデックス】
- PRIMARY KEY (id)
- UNIQUE KEY (email)

【制約】
- email: UNIQUE制約
- password: 最低8文字（ハッシュ化前の入力値で検証）

---

## テーブル: posts（投稿）

| カラム名      | 型           | NULL | デフォルト | 説明              |
|--------------|-------------|------|-----------|-------------------|
| id           | BIGINT      | NO   | AUTO      | 投稿ID（PK）       |
| user_id      | BIGINT      | NO   | -         | ユーザーID（FK）   |
| title        | VARCHAR(200)| NO   | -         | タイトル          |
| content      | TEXT        | NO   | -         | 本文              |
| published    | BOOLEAN     | NO   | false     | 公開状態          |
| created_at   | TIMESTAMP   | NO   | CURRENT   | 作成日時          |
| updated_at   | TIMESTAMP   | NO   | CURRENT   | 更新日時          |

【インデックス】
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (created_at)

【外部キー】
- user_id REFERENCES users(id) ON DELETE CASCADE

---

## テーブル: comments（コメント）

| カラム名      | 型           | NULL | デフォルト | 説明              |
|--------------|-------------|------|-----------|-------------------|
| id           | BIGINT      | NO   | AUTO      | コメントID（PK）   |
| post_id      | BIGINT      | NO   | -         | 投稿ID（FK）       |
| user_id      | BIGINT      | NO   | -         | ユーザーID（FK）   |
| content      | TEXT        | NO   | -         | コメント本文       |
| created_at   | TIMESTAMP   | NO   | CURRENT   | 作成日時          |
| updated_at   | TIMESTAMP   | NO   | CURRENT   | 更新日時          |

【インデックス】
- PRIMARY KEY (id)
- INDEX (post_id)
- INDEX (user_id)

【外部キー】
- post_id REFERENCES posts(id) ON DELETE CASCADE
- user_id REFERENCES users(id) ON DELETE CASCADE

【制約】
- content: 空文字不可、最大1000文字

---

## テーブル: tags（タグ）

| カラム名      | 型           | NULL | デフォルト | 説明              |
|--------------|-------------|------|-----------|-------------------|
| id           | BIGINT      | NO   | AUTO      | タグID（PK）       |
| name         | VARCHAR(50) | NO   | -         | タグ名            |
| created_at   | TIMESTAMP   | NO   | CURRENT   | 作成日時          |
| updated_at   | TIMESTAMP   | NO   | CURRENT   | 更新日時          |

【インデックス】
- PRIMARY KEY (id)
- UNIQUE KEY (name)

【制約】
- name: UNIQUE制約（同じ名前のタグは作れない）

---

## テーブル: post_tags（投稿タグ）

posts と tags の多対多を表す中間テーブル。

| カラム名      | 型           | NULL | デフォルト | 説明              |
|--------------|-------------|------|-----------|-------------------|
| post_id      | BIGINT      | NO   | -         | 投稿ID（PK, FK）   |
| tag_id       | BIGINT      | NO   | -         | タグID（PK, FK）   |
| created_at   | TIMESTAMP   | NO   | CURRENT   | 作成日時          |

【インデックス】
- PRIMARY KEY (post_id, tag_id)
- INDEX (tag_id)

【外部キー】
- post_id REFERENCES posts(id) ON DELETE CASCADE
- tag_id REFERENCES tags(id) ON DELETE CASCADE

【制約】
- 同じ投稿に同じタグを重複して付けられない（複合主キーで担保）
