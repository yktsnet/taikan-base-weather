## ローカル開発用 Docker 環境（App, DB, Mailpit, Redis）の構築
id: 08
skill: pr-workflow
branch-slug: local-docker-setup
github_issue:
status: open
type: feat
対象: docker-compose.yml (新規), src/Dockerfile (新規), src/.env.docker (新規), issues/done/08_local-docker-setup_pr.md (新規)
内容: Docker Composeを使用したローカル開発用のコンテナ環境を構築し、ホスト環境に依存せずコマンド一発でデータベース、メールキャッチャー、キュー連携を含めた開発環境が起動できるようにする。
確認: docker compose up がエラーなく正常に起動し、コンテナ内でマイグレーションが実行できること。
---
## 実装仕様

### 1. Dockerfile の作成
- **`src/Dockerfile`** [NEW]:
  - PHP 8.4-fpm をベースイメージとし、Laravel に必要な PHP 拡張（pdo_mysql, redis, bcmath 等）をインストールする。
  - Composer および Node.js/npm をマルチステージ等でインストールし、コンテナ内でのビルドを可能にする。

### 2. docker-compose.yml の作成
- **`docker-compose.yml`** [NEW]:
  - `app` (PHP/Vite用), `db` (MySQL 8.0), `mail` (Mailpit), `redis` (キャッシュ/キュー用) のサービスを定義する。
  - ホストとのマウント、およびコンテナ間のネットワーク接続を設定する。

### 3. 環境変数テンプレートの作成
- **`src/.env.docker`** [NEW]:
  - Docker 環境内で各コンテナ（`db`, `redis`, `mail`）に接続するための設定値を定義する。

---

## issues/done/08_local-docker-setup_pr.md の出力内容（必須）
Julesは、以下のテキストをそのままPR控えファイル `issues/done/08_local-docker-setup_pr.md` に含めて作成すること。

```markdown
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
```
