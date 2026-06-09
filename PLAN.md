# PLAN.md — 河川水位・気象モニタリング

> 作業ノート。README.md 作成時に統合する。

---

## プロジェクト概要

国土交通省の河川水位データ・気象庁の気象データを定期取得し、
Laravel + Inertia + React によるダッシュボードで可視化する。

IoT デバイス → HTTPS イベント受信という実案件パターンを、
公開 API ポーリング → SQS 投入で代替する。アーキテクチャは同一。

---

## アーキテクチャ

```
EventBridge (5分ごと)
    ↓
ECS Scheduled Task (poller)
    ↓ HTTP GET
国交省水文API / 気象庁API
    ↓
SQS (raw-events + DLQ)
    ↓
Laravel Queue Worker (ECS)
    ├── water_levels / weather_records 保存
    ├── 閾値チェック → alerts 生成
    └── SES アラートメール

RDS MySQL 8 ← 全テーブル
S3            ← CSV 日次アーカイブ

ALB → ECS (Laravel App) → Inertia + React
```

---

## データモデル

### stations（観測所マスタ）

| カラム | 型 | 備考 |
|---|---|---|
| id | bigint PK | |
| code | varchar | 国交省観測所コード |
| name | varchar | 観測所名 |
| river_name | varchar | 河川名 |
| prefecture | varchar | 都道府県 |
| lat / lng | decimal | 地図表示用 |
| warning_level | decimal | 注意水位 (m) |
| danger_level | decimal | 警戒水位 (m) |

### water_levels

| カラム | 型 | 備考 |
|---|---|---|
| id | bigint PK | |
| station_id | bigint FK | |
| observed_at | timestamp | 観測時刻 |
| level_m | decimal | 水位 (m) |
| alert_status | enum | normal/caution/warning/danger |

### weather_records

| カラム | 型 | 備考 |
|---|---|---|
| id | bigint PK | |
| station_id | bigint FK | 最近傍観測所に紐付け |
| observed_at | timestamp | |
| precipitation_mm | decimal | 降水量 (mm/h) |
| temperature_c | decimal | 気温 (℃) |

### alerts

| カラム | 型 | 備考 |
|---|---|---|
| id | bigint PK | |
| station_id | bigint FK | |
| triggered_at | timestamp | |
| level | enum | caution/warning/danger |
| level_m | decimal | トリガー時の水位 |
| notified | boolean | SES 送信済みフラグ |

---

## フェーズ

### Phase 1 — インフラ + データ収集 + 基本表示

#### Terraform

- [x] VPC / Subnets / Security Groups
- [x] RDS MySQL 8
- [x] SQS (raw-events + DLQ)
- [x] S3 (csv-archive)
- [ ] ECR (poller / app / worker)
- [ ] ECS Cluster (Fargate)
- [ ] EventBridge rule (5分 → ECS Scheduled Task)
- [ ] ALB + ACM
- [ ] IAM roles (ECS task role / SES送信 / S3アクセス)

#### Laravel セットアップ

- [x] Laravel 12 プロジェクト作成 (Vite 8 + React 19 + Tailwind v4)
- [x] Inertia.js + React + Vite
- [x] SQS Queue 接続設定 (aws-sdk/sqs)
- [x] Migration 実行 (stations / water_levels / weather_records / alerts)
- [x] Seeder: stations マスタ（関西圏 10 観測所）

#### Poller (ECS Scheduled Task)

- [x] 国交省水文水質 DB API クライアント実装 (ローカルモック自動生成)
- [x] 気象庁 API クライアント実装 (ローカルモック自動生成)
- [x] 取得データ正規化 → SQS 投入

#### Queue Worker

- [x] `ProcessWaterLevelEvent` Job
- [x] `ProcessWeatherEvent` Job
- [x] 閾値チェックロジック (station の warning/danger_level と比較)
- [x] `AlertNotification` Mailable (SES)

#### ダッシュボード (基本)

- [x] 観測所一覧 (テーブル + 警戒ステータスバッジ)
- [x] 観測所詳細 (直近水位 + 直近気象)
- [x] アラート履歴一覧

---

### Phase 2 — ダッシュボード充実 + 運用機能

- [ ] 水位推移グラフ (Chart.js、警戒水位ライン付き)
- [ ] 降雨量重ね表示 (棒グラフ + 折れ線)
- [ ] 観測所マップ (Leaflet.js、ステータス別ピン色)
- [ ] S3 CSV 日次アーカイブ + ダウンロードリンク
- [ ] 閾値カスタマイズ画面 (Inertia admin ページ)
- [ ] CI/CD (GitHub Actions → ECR push → ECS rolling deploy)
- [ ] Laravel Horizon (Queue 監視 UI)

---

## 未決事項

- [x] 国交省 API: 公式API非存在・スクレイピング規制のため、Poller内でのダミーデータ生成（現在時刻に基づき自動変動）を採用。
- [x] 気象庁 API: 非公式エンドポイントの不安定性を考慮し、一旦水位同様のモックデータ生成を採用。必要に応じて無料の Open-Meteo API を利用。
- [x] リポジトリ名決定: `kawa-watch`
- [x] AWS リージョン: ap-northeast-1 (東京) 固定で進める
- [ ] Horizon の採用可否: ポートフォリオ用途ならあり、コスト要確認
