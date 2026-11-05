# taskcheck（チーム業務共有ボード）

チームの中で「誰が・何を・いつまでに」進めているかを共有し、業務ごとにコメントでやりとりできる Web アプリです。
進み具合を確認するためだけの会議を減らし、期限の遅れにすぐ気づけるようにすることを目指しています。

## 解決したい課題

- 誰がどの業務を抱えているのかが、チーム内で分からない
- 期限が近い業務や遅れている業務に気づくのが遅れる
- 業務についての確認や相談が、メールや会議に散らばって追えなくなる
- 新しいメンバーが加わるたびに、タスクを1から説明しなければならない

## 主な機能

| 機能 | 内容 |
|---|---|
| 会員登録・ログイン | 所属チームを選んで登録する。ログインしたメンバーだけが使える |
| 業務の投稿・編集・削除 | タイトル・説明・期限・ステータス・画像（1枚）を登録する。編集・削除は投稿者だけ |
| チームの業務一覧 | 同じチームのメンバーの業務を、期限の近い順に表示する |
| 期限による色分け | 期限切れは赤 ⚠、期限間近（3日以内）はオレンジ ⏰、完了は灰色 ✓ |
| 検索・絞り込み | キーワード検索、期限切れ・期限間近・ステータスでの絞り込み |
| ブックマーク | ページを再読み込みせずに登録・解除できる（Ajax） |
| コメント | 業務の詳細画面で、質問や相談を書き込める |

## 使用技術

| 分類 | 技術 |
|---|---|
| フレームワーク | Laravel（PHP） |
| 認証 | Laravel Breeze（Blade 版） |
| 画面 | Blade、Tailwind CSS、JavaScript（`fetch`） |
| データベース | MySQL |
| 開発環境 | Docker（Laravel Sail） |
| 公開環境 | Render |

選定理由は [docs/06_tech-selection/tech-selection.md](./docs/06_tech-selection/tech-selection.md) を参照。

## 設計資料

| 資料 | ファイル |
|---|---|
| 設計の全体像 | [docs/00_overview.md](./docs/00_overview.md) |
| 要件定義書 | [docs/requirements.md](./docs/requirements.md) |
| 画面設計（Figma） | [docs/01_screen-design/figma.md](./docs/01_screen-design/figma.md) |
| DB 設計（ER 図・テーブル定義） | [docs/02_database-design/](./docs/02_database-design/) |
| ルーティング設計 | [docs/03_routing-design/routing.md](./docs/03_routing-design/routing.md) |
| 工数見積もり・スケジュール | [docs/04_estimation/](./docs/04_estimation/) |
| インフラ構成図 | [docs/05_infrastructure/infrastructure.md](./docs/05_infrastructure/infrastructure.md) |
| 技術選定 | [docs/06_tech-selection/tech-selection.md](./docs/06_tech-selection/tech-selection.md) |
| 開発計画（スプリント計画） | [docs/sprint-plan.md](./docs/sprint-plan.md) |
| 見積書 | [docs/estimate.md](./docs/estimate.md) |
| 画面モック | [docs/mock.html](./docs/mock.html)（ブラウザで開く） |

タスクは GitHub Issues で管理し、GitHub Projects のボードで進捗を確認します。

## 開発スケジュール

| 週 | 内容 |
|---|---|
| Week 18〜21 | 要件定義・設計・開発計画 |
| Week 22 | 環境構築・認証・業務の投稿と一覧（MVP 前半） |
| Week 23 | 詳細・編集・削除・権限・画像・色分け・検索・ブックマーク（MVP 完成） |
| Week 24 | コメント・絞り込み・ブックマーク一覧・テスト・デプロイ |

## フォルダ構成

```
task-checklist/
├── README.md
├── docs/   # 要件定義・設計・開発計画の資料
└── src/    # アプリのソースコード（Week 22 から追加）
```

## 環境構築

Week 22 にアプリを作り始めたあとで、`src/` の起動手順をここに書きます。
