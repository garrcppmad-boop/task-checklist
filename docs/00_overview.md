# 設計概要：チーム業務共有ボード

> 要件定義書（[requirements.md](./requirements.md)）をもとにした設計の全体像。
> 各設計の詳細は、下の「設計ドキュメント一覧」のファイルを参照。

## 1. アプリの概要

| 項目 | 内容 |
|---|---|
| アプリ名 | チーム業務共有ボード |
| 目的 | チーム内で「誰が・何を・いつまでに」進めているかを共有し、業務ごとにコメントでやりとりできるようにする |
| 利用者 | 所属チームのメンバー（ログインしたメンバーだけが閲覧できる） |
| 解決する課題 | 誰が何を抱えているか分からない／期限切れに気づくのが遅れる／相談がチャットに流れて追えない |
| 納期 | 3週間 |

### ユーザーとできること

| ユーザー | できること |
|---|---|
| 未登録ユーザー | 会員登録（所属チームを選ぶ）・ログイン・パスワードリセット |
| 会員ユーザー | チームの業務の閲覧・検索・絞り込み、業務の投稿、コメント、ブックマーク |
| 投稿者 | 自分が投稿した業務だけ編集・削除できる（他の人は 403） |

---

## 2. 機能の一覧

| 優先度 | 機能 |
|---|---|
| **Must** | 会員登録（F-01）／ログイン・ログアウト（F-02）／パスワードリセット（F-03）／業務の投稿（F-04）／チームの業務一覧（F-05）／自分の業務一覧（F-06）／業務の詳細（F-07）／編集・削除（F-08）／画像の添付（F-09）／キーワード検索（F-11）／期限による色分け（F-13）／ブックマーク・Ajax（F-14）／権限管理・Policy（F-15） |
| **Should** | コメント（F-10）／期限・ステータスでの絞り込み（F-12）／ブックマーク一覧（F-16） |
| **Could** | ステータスの Ajax 更新（F-17）／自分のコメントの削除（F-18）／画像の S3 保存・CloudFront 配信（F-19） |

### 期限による色分け（F-13）

| 状態 | 条件 | 表示 |
|---|---|---|
| 期限切れ | 期限を過ぎていて未完了 | 赤＋⚠ |
| 期限間近 | 期限まで3日以内で未完了 | オレンジ＋⏰ |
| 通常 | 期限まで4日以上 | 通常の文字色 |
| 完了 | ステータスが「完了」 | 灰色＋✓ |

---

## 3. システム構成

### 使用技術

| 分類 | 技術 |
|---|---|
| フレームワーク | Laravel（PHP） |
| 認証 | Laravel Breeze（Blade 版） |
| 画面 | Blade テンプレート、Tailwind CSS |
| Ajax | JavaScript の `fetch`（CSRF トークンをヘッダーに付ける） |
| データベース | MySQL |
| 画像の保存 | ローカル（`storage/app/public`）。公開環境では Persistent Disk |
| セッション・キャッシュ | Render Key Value（Redis 互換） |
| 公開環境 | Render（`main` への push で自動デプロイ、HTTPS） |
| バージョン管理 | Git / GitHub |

### アプリの構成（MVC）

```
ブラウザ ──HTTPS──▶ ルーティング（routes/web.php）
                        │  auth ミドルウェア（ログイン必須）
                        ▼
                   コントローラー ──▶ Policy（権限チェック：同じチーム／投稿者だけ）
                        │
                        ├──▶ モデル（Eloquent）──▶ MySQL
                        │
                        └──▶ ビュー（Blade）──▶ HTML ／ JSON（Ajax）
```

| コントローラー | 担当 |
|---|---|
| TaskController | 業務の一覧・自分の一覧・作成・詳細・編集・削除、検索・絞り込み |
| TaskStatusController | ステータスの Ajax 更新（Could） |
| CommentController | コメントの投稿・削除 |
| BookmarkController | ブックマークの登録・解除（Ajax）、ブックマーク一覧 |
| ProfileController | プロフィール（Breeze） |

| Policy | 判定すること |
|---|---|
| TaskPolicy | 閲覧：同じチームの業務だけ／編集・削除：投稿者だけ |
| CommentPolicy | 削除：自分のコメントだけ |

---

## 4. データ構造

```
teams (1) ----< (N) users
users (1) ----< (N) tasks
users (1) ----< (N) comments
tasks (1) ----< (N) comments
users (N) >----< (N) tasks  [bookmarks: 中間テーブル]
```

| テーブル | 内容 |
|---|---|
| teams | チーム（Seeder であらかじめ登録） |
| users | ユーザー（所属チームを1つ持つ） |
| tasks | 業務（タイトル・説明・画像・期限・ステータス） |
| comments | 業務へのコメント |
| bookmarks | ブックマーク（ユーザーと業務の組み合わせは重複不可） |

---

## 5. 画面とURL

| No | 画面 | URL | 備考 |
|---|---|---|---|
| 1 | トップページ | `/` | ログイン済みなら業務一覧へ、未ログインならログイン画面へ |
| 2 | 会員登録 | `/register` | Breeze に所属チームの選択を追加 |
| 3 | ログイン | `/login` | Breeze |
| 4 | パスワードリセット | `/forgot-password` | Breeze |
| 5 | チームの業務一覧 | `/tasks` | ログイン後のホーム。検索・絞り込み・色分け・ブックマーク |
| 6 | 自分の業務一覧 | `/tasks/mine` | |
| 7 | ブックマーク一覧 | `/bookmarks` | |
| 8 | 業務の新規投稿 | `/tasks/create` | 画像アップロード |
| 9 | 業務の詳細 | `/tasks/{task}` | コメント投稿・ブックマーク |
| 10 | 業務の編集 | `/tasks/{task}/edit` | 投稿者のみ |
| 11 | プロフィール | `/profile` | Breeze |

---

## 6. 非機能要件の対応方針

| 要件 | 設計での対応 |
|---|---|
| ページの読み込み 5秒以内 | 一覧は1ページ20件、Eager Loading（`with()`）で N+1 を防ぐ、期限・ステータスにインデックス |
| セキュリティ | 全ページ `auth` ミドルウェア、Policy で権限チェック、Blade の `{{ }}` でエスケープ、CSRF トークン、Eloquent で SQL を組み立てる、パスワードは bcrypt、HTTPS |
| 画像 | jpg / png / webp、2MB までをバリデーション |
| 使いやすさ | エラーは項目ごとに日本語で表示、完了メッセージ、削除前の確認ダイアログ、レスポンシブ対応 |
| 保守性 | PSR-12、Feature テスト（ログイン・CRUD・権限）、機能ごとにコミット |

---

## 7. 設計ドキュメント一覧

| No | 設計 | ファイル | 内容 |
|---|---|---|---|
| ― | 要件定義 | [requirements.md](./requirements.md) | 目的・ユーザーストーリー・機能要件・非機能要件・制約 |
| 01 | 画面設計 | [01_screen-design/figma.md](./01_screen-design/figma.md) | Figma のデザイン（共有リンク）、画面遷移図 |
| 02 | DB設計 | [02_database-design/er-diagram.md](./02_database-design/er-diagram.md)、[02_database-design/table-definitions.md](./02_database-design/table-definitions.md)、[02_database-design/migration-plan.md](./02_database-design/migration-plan.md) | ER 図、テーブル定義、マイグレーション計画（作る順番・Seeder） |
| 03 | ルーティング設計 | [03_routing-design/routing.md](./03_routing-design/routing.md) | 画面とURL・HTTPメソッド・コントローラー・権限の一覧 |
| 04 | 見積もり | [04_estimation/workload.md](./04_estimation/workload.md)、[04_estimation/schedule.md](./04_estimation/schedule.md) | 工数見積もり（開発98時間＋バッファ25時間）、3週間のスケジュール |
| 05 | インフラ構成 | [05_infrastructure/infrastructure.md](./05_infrastructure/infrastructure.md) | Render を使った公開環境の構成図 |

---

## 8. 開発の進め方

| 週 | 内容 |
|---|---|
| 1週目 | 環境構築・DB・認証（会員登録にチーム選択を追加）・業務の作成と一覧 |
| 2週目 | 業務の詳細・編集・削除、権限、画像、期限の色分け、検索、ブックマーク（Must を完成） |
| 3週目 | 絞り込み・コメント・ブックマーク一覧、テスト、README、デプロイ |

詳しくは [04_estimation/schedule.md](./04_estimation/schedule.md) を参照。

## 9. 未決事項（Week 21 の要件見直しで確定する）

| No | 内容 | 関係する設計 |
|---|---|---|
| 1 | 他のチームの業務も見られるようにするか（現在の設計は「同じチームだけ」） | ルーティング（権限）、TaskPolicy |
| 2 | 削除した業務をあとで戻せるようにするか（論理削除にするか） | DB 設計（tasks に deleted_at を追加するか） |
| 3 | 2MB を超えるスマホの写真の扱い（上限を上げるか、縮小するか） | 画像アップロード |
| 4 | 公開環境の MySQL をどこに用意するか（Render 上に立てるか、外部サービスを使うか） | インフラ構成 |
