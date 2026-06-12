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

### Phase 1 — ローカルAWSサンドボックス統合 + 基本機能

#### LocalStack / Terraform
- [ ] LocalStack (無料版) をローカル環境 (Docker Compose) へ導入
- [ ] LocalStack向けTerraformプロバイダ設定の追加
- [ ] SQSキュー・S3バケットのLocalStackへのプロビジョニング (`terraform apply`)
- [ ] Laravel / WorkerからのLocalStack SQS/S3への接続疎通確認
- [ ] (本番用) ECR / ECS Cluster / EventBridge / ALB 等のTerraform定義の整理

#### Laravel セットアップ & 基本実装 (完了済み)
- [x] Laravel 12 プロジェクト作成 (Vite 8 + React 19 + Tailwind v4)
- [x] Inertia.js + React + Vite の初期構成
- [x] SQS Queue 接続設定 (aws-sdk/sqs)
- [x] Migration 実行 (stations / water_levels / weather_records / alerts)
- [x] Seeder: stations マスタ（関西圏 10 観測所）
- [x] 擬似Pollerコマンド (`app:poll-water-level`, `app:poll-weather`) 実装
- [x] Queue Worker (`ProcessWaterLevelEvent`, `ProcessWeatherEvent`) とメール通知実装
- [x] ダッシュボード基本画面 (一覧、詳細、アラート履歴)

---

### Phase 2 — 可視化の強化 + 高トラフィック受信の最適化

#### ダッシュボードの可視化強化
- [ ] **水位推移グラフ (Chart.js / react-chartjs-2)**:
  - 注意水位・警戒水位の境界ラインを重ねた水位・降雨量（棒・折れ線）の複合グラフ表示
- [ ] **観測所マップ (Leaflet.js / React-Leaflet)**:
  - 地図上に10箇所の観測所をピン表示。警戒状況（normal/warning/danger等）に応じた色の動的変化

#### 高トラフィックデータ受信の最適化（負荷検証 & チューニング）
- [ ] **負荷テスト用Pollerコマンドの追加**:
  - 数千〜数万件規模の擬似センサーイベントを瞬時にSQSへ一括投入する機能の実装
- [ ] **Queue Workerのバルク処理化**:
  - メッセージ受信・処理時のデータベース保存をバルクインサートに変更し、DBへのI/O負荷を低減する
- [ ] **マルチプロセスWorkerとデータ整合性の担保**:
  - Workerをマルチプロセスで並行起動した際の処理効率化と、同一観測所データの同時書き込みにおけるデッドロック回避（トランザクションとDBロックの最適化）

#### 運用機能
- [ ] **S3 CSV日次アーカイブ**:
  - 前日の水位データを日次バッチで自動CSV化し、LocalStack S3バケットへアップロード＆ダッシュボードからダウンロード可能にする機能
- [ ] **Laravel Horizon (Queue 監視 UI) の導入**:
  - 大量ジョブ実行時のキューの滞留状況を可視化・監視する

---

## 未決事項・設計判断

- [x] **AWSモックサンドボックス**: LocalStack (Community版) を採用し、SQS・S3をローカル環境でTerraformを用いて構築可能とする。これにより本番AWS移行がスムーズになる。
- [x] **高トラフィック対策の軸**: 水位計デバイスからの「大量データ受信・非同期処理」の最適化を主軸とし、バルク処理や並列実行時の整合性確保を実装する。
- [x] **可視化ライブラリ**: グラフは `Chart.js`、地図は `Leaflet.js` を採用。軽量で React 19 / Inertia 環境との親和性が高い。
- [ ] **本番AWSデプロイ手順の整備**: LocalStack向け設定から本番AWS環境への切り替え検証方法をドキュメント化する。
