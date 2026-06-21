# TaikanBaseWeather（体感ベース天気）

全国47都道府県の現在の天気を表示し、「今日は過去20年で何番目に暑い/寒い日か」をランキングで提示する Web アプリ。
「最近暑い気がする」「今年は例年より寒い？」といった体感を、気象庁の実データで裏付ける。

---

## アーキテクチャ

```
スケジューラ (5分ごと)
    ↓
WeatherPoller (php artisan app:poll-weather)
    ↓ HTTP GET
JMA AMeDAS API (リアルタイム気象)
    ↓
SQS (raw-events + DLQ)
    ↓
BulkQueueWorker
    ├── weather_records 保存（バルクインサート）
    ├── ランキング計算 → キャッシュ更新
    └── 極端値アラート（メール通知）

MySQL 8 ← 全テーブル
```

---

## 技術スタック

| 種別 | 技術 |
|---|---|
| バックエンド | Laravel 12 / PHP 8.3 |
| フロントエンド | React 19 / Inertia.js 2.0 / Vite 8 |
| DB | MySQL 8 |
| キュー/非同期 | AWS SQS + BulkQueueWorker（バルクインサート） |
| キャッシュ | Laravel Cache (file / Redis) |
| スタイリング | Tailwind CSS v4 |
| 地図 | Leaflet.js / React-Leaflet |
| IaC | Terraform + LocalStack |

---

## データソース

| データ | ソース | 用途 |
|---|---|---|
| リアルタイム気象 | JMA AMeDAS API | 現在の気温・降水量 |
| 過去20年の日別気温 | JMA 過去の気象データ検索 | ランキング算出 |

---

## 主要機能

| 機能 | 説明 |
|---|---|
| ダッシュボード | 全国47地点の現在の天気 + 過去20年中のランク表示 |
| 地点詳細（カレンダー） | 月カレンダーで日別の最高気温とランクをヒートマップ表示 |
| 管理者パネル | SQS キュー監視、DB 書き込み件数、負荷テスト実行 |
| アラート通知 | 過去20年で1位の極端気温時にメール通知 |
| 負荷テスト | 過去データ大量投入のシミュレーション（SQS バルク処理） |

---

## ローカル開発環境の構築

### Docker Compose による構築（推奨）

#### 1. 前提条件
* Docker / Docker Desktop がインストールされ、デーモンが起動していること

#### 2. 初回セットアップ & 起動
```bash
docker compose up -d --build

docker compose exec app composer install
docker compose exec app npm install
docker compose exec app cp .env.docker .env
docker compose exec app php artisan key:generate

docker compose exec app php artisan migrate --seed
```

#### 3. 開発サーバー（Vite）の起動
```bash
docker compose exec app npm run dev
```

#### 4. アクセス先
* **ダッシュボード**: [http://localhost:8000](http://localhost:8000)
* **Mailpit (メールキャッチャー)**: [http://localhost:8025](http://localhost:8025)

---

## 自動テスト

```bash
# テスト実行
docker compose exec app php artisan test

# コードスタイルチェック
docker compose exec app ./vendor/bin/pint --test

# 静的解析
docker compose exec app ./vendor/bin/phpstan analyse
```
