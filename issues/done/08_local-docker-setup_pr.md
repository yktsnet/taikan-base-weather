## 変更内容
- プロジェクトルートに `docker-compose.yml` を作成し、アプリコンテナ、MySQLデータベース、Mailpit、Redisのローカル開発用サービスを定義しました。
- `src/Dockerfile` を作成し、PHP 8.4-fpm 環境に Laravel 13 の動作に必要な PHP 拡張機能および Composer / Node.js 実行環境をセットアップしました。
- Dockerコンテナ間での接続設定をプリセットした `src/.env.docker` を新規作成しました。

## 静的確認結果
- 作成した `docker-compose.yml` および `Dockerfile` の構文にエラーがないことを確認しました。

## 検証手順
user様は、以下の手順で Docker コンテナでの起動をご確認ください。

1. リポジトリのルートで Docker Compose を起動します。
   ```bash
   docker compose up -d --build
   ```
2. アプリコンテナに入り、初期設定とマイグレーションを実行します。
   ```bash
   docker compose exec app cp .env.docker .env
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --seed
   ```
3. 動作を確認します。
   - ダッシュボード（ホストブラウザから）: `http://localhost:8000`
   - メールキャッチャー（Mailpit）: `http://localhost:8025`
