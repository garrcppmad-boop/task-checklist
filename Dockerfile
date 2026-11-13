# syntax=docker/dockerfile:1
#
# 本番（Render）用の Dockerfile（マルチステージビルド）
#   ステージ1 vendor : Composer で PHP の依存パッケージをインストール（--no-dev）
#   ステージ2 assets : Node.js で CSS/JS をビルド（npm run build → public/build）
#   ステージ3 runtime: 実行に必要なものだけをコピーした最終イメージ
#
# Week 16 の Dockerfile（month2/laravel-docker-app/Dockerfile）をもとにしている。
# Week 16 からの変更点には「変更:」とコメントを付けた。
#
# 変更: Laravel は src/ に置いているため、ビルドの起点（コンテキスト）はリポジトリ直下にして、
#       COPY するパスには src/ を付ける
#
# ローカル開発用は docker/php/Dockerfile（docker-compose.yml から使用）
#
#   ビルド: docker build -t task-checklist:prod .
#   起動  : docker run --rm -p 10000:10000 --env-file src/.env task-checklist:prod

ARG PHP_VERSION=8.2

# ---------------------------------------------------------------
# ステージ1: PHP の依存パッケージ（Composer）
# ---------------------------------------------------------------
FROM php:${PHP_VERSION}-cli-alpine AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# composer.json / composer.lock だけ先にコピーしてインストール
# → ソースコードを変更しても、依存が変わらなければこの層はキャッシュが効く
COPY src/composer.json src/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist

# アプリのコードをコピーして、最適化したオートローダーを生成
# （composer.json のスクリプトで Laravel のパッケージ検出 package:discover も実行される）
COPY src/ .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

# ---------------------------------------------------------------
# ステージ2: フロントエンドのビルド（Vite）
# ---------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY src/package.json src/package-lock.json ./
RUN npm ci --no-audit --no-fund

# 変更（1）: Breeze が作った Tailwind CSS の設定ファイル（tailwind.config.js・postcss.config.js）も
#           コピーしてから npm run build する。
#           これがないと @vite が読み込む CSS・JS が作られず、本番の画面がエラーになるか、見た目が崩れる
COPY src/vite.config.js src/tailwind.config.js src/postcss.config.js ./
COPY src/resources ./resources
# Tailwind が使用クラスを探す対象（tailwind.config.js の content）に Laravel のページネーションの
# テンプレートも含まれるため、vendor ステージからコピーしておく
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------------------------------------------------------------
# ステージ3: 実行用イメージ（Alpine Linux + PHP）
# ---------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

# PHP 拡張のインストール
#   pdo_mysql: MySQL への接続（ローカル開発の docker-compose と同じ DB）
#   pdo_pgsql: 変更（2）: Render の DB は PostgreSQL なので追加
#   opcache  : PHP のコンパイル結果をキャッシュして高速化
#   gd       : 画像（JPEG / PNG / WebP）を扱う（F-09 画像アップロード）
#   redis    : セッション・キャッシュを Render Key Value（Redis 互換）に保存（phpredis）
# apk upgrade: ベースイメージ作成後に修正版が出た OS パッケージの脆弱性を取り込む
# ビルドにだけ必要なパッケージ（.build-deps）は最後に削除してイメージを小さく保つ
# 変更（2）: 実行時に必要な PostgreSQL のライブラリ libpq と、ビルド用の postgresql-dev を追加
RUN apk upgrade --no-cache \
 && apk add --no-cache libpng libjpeg-turbo libwebp libpq \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev libwebp-dev postgresql-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql opcache gd \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps \
 && rm -rf /tmp/* /usr/src/php*

# 本番用の PHP 設定
# 変更: 画像は 2MB まで受け付ける（F-09）ため、PHP の上限（初期値 2M）に少し余裕を持たせる
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > "$PHP_INI_DIR/conf.d/opcache-production.ini" \
 && { \
        echo 'upload_max_filesize=4M'; \
        echo 'post_max_size=8M'; \
    } > "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /var/www/html

# 前のステージから必要なものだけをコピー（所有者は非rootユーザー www-data）
COPY --chown=www-data:www-data src/ .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=vendor /app/bootstrap/cache ./bootstrap/cache
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Laravel が書き込むディレクトリを用意
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
 && chown -R www-data:www-data storage bootstrap/cache public

# 非rootユーザーで実行
USER www-data

# 変更: Week 16 は PHP-FPM（9000番）だったが、Render では php artisan serve で HTTP を直接受ける
#       （FPM 用の ping ヘルスチェックも使わないので外した。Render は自分で HTTP のヘルスチェックをする）
EXPOSE 10000

# 変更（3）: 起動時にマイグレーションを実行してから、Render が渡すポート（環境変数 PORT）で待ち受ける
#   - migrate --force   : 本番（APP_ENV=production）では確認を聞かれるため --force で自動実行する
#   - db:seed --force   : 会員登録で選ぶチームを作る（F-01）。本番では TeamSeeder だけが動き、
#                         firstOrCreate なので毎回実行しても重複しない（デモデータは APP_ENV=local のときだけ）
#   - storage:link      : 添付画像を /storage/... で表示するためのリンク（public/storage）を作る
#   - --host=0.0.0.0    : コンテナの外からのアクセスを受け付ける
#   - ${PORT:-10000}    : Render が渡すポート番号。なければ 10000
#   - exec              : serve を PID 1 にして、Render の停止シグナルを直接受け取れるようにする
# php artisan serve は開発用サーバー。長期運用するなら Nginx + PHP-FPM などへの切り替えを検討する
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan storage:link && exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
