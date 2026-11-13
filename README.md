# taskcheck（チーム業務共有ボード）

## 📝 プロジェクト概要

チームの中で「誰が・何を・いつまでに」進めているかを共有する Web アプリケーションです。
メンバーは業務を投稿し、同じチームの業務を期限の近い順に一覧・検索・ブックマークできます。
進み具合を確認するためだけの会議を減らし、期限の遅れにすぐ気づけるようにすることを目指しています。

**解決したい課題**
- 誰がどの業務を抱えているのかが、チーム内で分からない
- 期限が近い業務や遅れている業務に気づくのが遅れる
- 業務についての確認や相談が、メールや会議に散らばって追えなくなる
- 新しいメンバーが加わるたびに、タスクを1から説明しなければならない

**公開URL:** https://（Render のサービス名）.onrender.com
**設計書:** [docs/](./docs/) フォルダ（要件定義書・設計書・ルーティング設計）

| 資料 | ファイル |
|---|---|
| 要件定義書 | [docs/requirements.md](./docs/requirements.md) |
| 画面設計（Figma） | [docs/01_screen-design/figma.md](./docs/01_screen-design/figma.md) |
| DB 設計（ER 図・テーブル定義） | [docs/02_database-design/](./docs/02_database-design/) |
| ルーティング設計 | [docs/03_routing-design/routing.md](./docs/03_routing-design/routing.md) |
| インフラ構成図 | [docs/05_infrastructure/infrastructure.md](./docs/05_infrastructure/infrastructure.md) |
| 技術選定 | [docs/06_tech-selection/tech-selection.md](./docs/06_tech-selection/tech-selection.md) |
| 開発計画（スプリント計画） | [docs/sprint-plan.md](./docs/sprint-plan.md) |

## ✨ 主な機能

- ✅ 会員登録（所属チームを選択）・ログイン・ログアウト・パスワードリセット
- ✅ 業務の投稿（タイトル、説明、期限、ステータス、画像）・チームの業務一覧・自分の業務一覧・詳細表示
- ✅ 業務の編集・削除（Policy で投稿者本人のみ。他のチームの業務は閲覧もできない）
- ✅ キーワード検索・期限切れ／期限間近／ステータスでの絞り込み
- ✅ 期限による色分け（期限切れは赤 ⚠、3日以内はオレンジ ⏰、完了は灰色 ✓）
- ✅ 画像アップロード（1枚、jpg / png / webp、2MB まで）
- ✅ ブックマーク機能（Ajax、ページを再読み込みしない）
- ✅ ページネーション（1ページ20件、検索条件を引き継ぐ）

## 🛠 技術スタック

### バックエンド
- PHP 8.2
- Laravel 12.x
- Laravel Breeze（認証）
- MySQL 8.0（開発環境）／ PostgreSQL（本番環境）

### フロントエンド
- Blade（Laravelテンプレート）
- Tailwind CSS
- Alpine.js
- JavaScript（`fetch` によるブックマークの Ajax）
- Vite

### インフラ
- Docker（開発環境：Nginx + PHP-FPM + MySQL + phpMyAdmin）
- 本番環境: Render（Docker。マルチステージビルドの [Dockerfile](./Dockerfile)）

### 開発ツール
- Git/GitHub（Issues・Projects でタスク管理）
- Claude Code
- PHPUnit

## 📦 セットアップ方法

### 1. リポジトリのクローン
```bash
git clone https://github.com/garrcppmad-boop/task-checklist.git
cd task-checklist   # Laravel は src/、設計書は docs/ にある
```

### 2. 依存関係のインストール
```bash
docker compose up -d
docker compose exec app composer install
cd src && npm install && cd ..   # ホストPCで実行（appコンテナにはNode.jsが入っていないため）
```

### 3. 環境設定
```bash
cp src/.env.example src/.env
docker compose exec app php artisan key:generate
```

### 4. データベース設定
`src/.env`ファイルを編集:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=secret
```

### 5. マイグレーション実行
```bash
docker compose exec app php artisan migrate --seed
```

`--seed` を付けると、会員登録で選ぶチームが作られます。
開発環境（`APP_ENV=local`）では、デモ用のユーザーと業務も作られます。

| メールアドレス | パスワード |
|---|---|
| demo1@example.com 〜 demo9@example.com | password |

### 6. ストレージリンク作成
```bash
docker compose exec app php artisan storage:link
```

### 7. 開発サーバー起動
```bash
docker compose up -d   # 2. で起動済みなら不要
cd src && npm run dev   # ホストPCで実行
```

アプリケーションはブラウザで localhost にアクセスすると表示されます（nginxの80番ポート）。
phpMyAdmin は localhost:8080 で開けます。

### 8. テストの実行
```bash
docker compose exec app php artisan test
```

テストはメモリ上の SQLite で動くので、開発用の MySQL のデータは消えません。

## 📸 スクリーンショット

### チームの業務一覧
![チームの業務一覧](screenshots/index.png)

### 業務の詳細
![業務の詳細](screenshots/show.png)

### 業務の投稿
![業務の投稿](screenshots/create.png)

## 💡 工夫した点

1. **セキュリティ対策**
   - CSRF保護、XSS対策（Blade の `{{ }}` でエスケープ）、SQLインジェクション対策（Eloquent・プレースホルダ）を実施し、Feature テストで攻撃用の入力を送って確認
   - 検索の `%` や `_` をエスケープし、「何にでも一致する」検索にならないようにした
   - ファイルアップロードのバリデーション（ファイルの中身で形式を判定し、偽装した PHP・SVG・HTML を拒否。保存名はランダムにする）
   - 本番では `APP_DEBUG=false`、`.env` は Git に含めない

2. **パフォーマンス最適化**
   - N+1問題の解決（`with`・`withCount`・`withExists` による Eager Loading）。件数を増やしても SQL の回数が変わらないことをテストで確認
   - 期限・ステータスの列にインデックスを付け、期限順の並び替えと絞り込みを速くした

3. **UI/UX**
   - 期限切れ・期限間近を色とマークで表示し、遅れにすぐ気づけるようにした
   - ブックマークは Ajax で、ページを再読み込みせずに切り替えられる
   - Tailwind CSS でレスポンシブ対応（PC・スマホ）
   - 期限の判定は日本時間（`Asia/Tokyo`）で行う

4. **コード品質**
   - Policy で権限管理（編集・削除は投稿者だけ、閲覧は同じチームだけ）
   - バリデーションの分離（FormRequest）
   - 画像の保存・差し替え・削除を Service クラスにまとめ、S3 に移すときは保存先の設定を変えるだけにした
   - 検索・絞り込みの条件を Eloquent のローカルスコープにまとめた
   - CRUD・認証・権限・バリデーション・画像・検索・ページネーション・セキュリティの Feature テストを作成

## 🚀 今後の拡張予定

- [ ] コメントの投稿（F-10）※ 詳細画面での表示はできている
- [ ] ブックマーク一覧（F-16）
- [ ] ステータスをその場で変更（F-17、Ajax）
- [ ] 自分のコメントの削除（F-18）
- [ ] 画像の S3 保存・CloudFront 配信（F-19）
- [ ] エラーメッセージの日本語化

## 📝 学習記録

このプロジェクトは、techmeetsのAI駆動開発カリキュラム（6ヶ月）の集大成として作成しました。

### 習得した技術
- Laravel（MVC、認証、Eloquent ORM、Policy、FormRequest）
- Docker（コンテナ化、本番用のマルチステージビルド）
- Git/GitHub（バージョン管理、ブランチと PR による開発、Issues・Projects でのタスク管理）
- Render（Docker でのデプロイ、PostgreSQL、環境変数の管理）
- テスト（PHPUnit の Feature テスト）
- 設計（要件定義、DB設計、ルーティング設計、インフラ構成、スプリント計画）

## 👤 作成者

- GitHub: [@garrcppmad-boop](https://github.com/garrcppmad-boop)

## 📄 ライセンス

MIT License
