# kawa-watch (河川水位・気象モニタリングシステム)
河川の水位データおよび気象データを定期取得・監視し、管理者へのアラート通知やダッシュボードでの可視化を行う Web アプリ。
Laravel (Inertia + React) によるイベント駆動型モニタリングシステム。

---

## アーキテクチャ

```
EventBridge (5分ごと)
    ↓
ECS Scheduled Task (poller: php artisan app:poll-* コマンド)
    ↓ HTTP GET (※モック)
水位データ / 気象データ
    ↓
SQS (raw-events + DLQ)
    ↓
Laravel Queue Worker (ECS)
    ├── water_levels / weather_records 保存
    ├── 閾値チェック → alerts 生成
    └── SES アラートメール (AlertNotification)

RDS MySQL 8 (本番) / SQLite (ローカル)
S3 (CSV日次アーカイブ ※予定)

ALB → ECS (Laravel App) → Inertia + React (Vite 8)
```

---

## 技術スタック

| 種別 | 技術 |
|---|---|
| バックエンド | Laravel 12 / PHP 8.3 |
| フロントエンド | React 19 / Inertia.js 2.0 / Vite 8 |
| DB | MySQL 8 (本番) / SQLite (ローカル開発) |
| キュー/非同期 | AWS SQS + Laravel Queue Worker |
| スタイリング | Tailwind CSS v4 |
| メール (警告) | AWS SES (Mailable) / Mailhog (開発) |

---

## データモデル概要

```
stations（観測所マスタ）
  ├── water_levels（水位記録）
  ├── weather_records（気象記録）
  └── alerts（アラート履歴）
```

---

## WSL ローカル開発

### 前提
- WSL2 (Ubuntu) + PHP 8.3 / Node.js (Vite動作環境) がインストール済みであること

### 初回セットアップ

```bash
# 1. リポジトリのクローン
git clone git@github.com:kyamakawa-widget/kawa-watch.git
cd kawa-watch/src

# 2. 依存関係のインストール
composer install
npm install

# 3. 環境設定ファイルの準備とキー生成
cp .env.example .env
php artisan key:generate

# 4. SQLite データベースの作成と接続設定 (MySQL設定の無効化)
touch database/database.sqlite
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env
sed -i 's/^DB_HOST=/#DB_HOST=/g' .env
sed -i 's/^DB_PORT=/#DB_PORT=/g' .env
sed -i 's/^DB_DATABASE=/#DB_DATABASE=/g' .env
sed -i 's/^DB_USERNAME=/#DB_USERNAME=/g' .env
sed -i 's/^DB_PASSWORD=/#DB_PASSWORD=/g' .env

# 5. セッションとキャッシュを file に変更 (SQLエラーの回避)
sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/g' .env
sed -i 's/CACHE_STORE=database/CACHE_STORE=file/g' .env

# 6. マイグレーションと初期マスタデータ (シード) の投入
php artisan migrate --seed

# 7. アセットのビルド
npm run build
```

### 起動方法

```bash
# ターミナル1: Vite開発サーバーの起動
npm run dev

# ターミナル2: Laravelローカルサーバーの起動
php artisan serve
```
→ [http://localhost:8000](http://localhost:8000) でアクセス可能

---

## 主要機能

| 機能 | 説明 |
|---|---|
| ダッシュボード | 観測所一覧・最新の水位/気象データのリアルタイム監視（警戒ステータスバッジ表示） |
| 観測所詳細 | 特定の観測所の基本情報および直近24件の水位・気象履歴の表示 |
| アラート履歴 | 発生した警告ログ（注意・警戒・危険水位の超過履歴）の一覧表示 |
| データ収集バッチ (Poller) | 水位データ（`app:poll-water-level`）および気象データ（`app:poll-weather`）を取得し、SQSへイベント送信するコマンド |
| 非同期処理 (Worker) | SQSからデータを受信し、データベース保存・閾値超過時のアラート登録とSESメール通知（`AlertNotification`）を非同期実行するジョブ |
