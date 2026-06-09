# Issue 03以降の開発計画（Phase 1完了まで）

このドキュメントは、河川水位・気象モニタリングシステム（kawa-watch）の開発において、現在クローズ済みのIssue 02（Laravel 11のスケルトン定義およびデータベーススキーマ構築）に続く、Issue 03以降の実装プランと設計案を示すものです。

---

## 1. 提案するIssueの構成案

各セッションの粒度が「1セッションで完走できる量（対象ファイル7本以下）」になるよう、Phase 1の残りの作業を4つのIssueに分解して進めることを提案します。

```
[Issue 02: Skeleton & Migrations] (完了)
  ↓
[Issue 03: Frontend Set up & Seeder]
  ↓
[Issue 04: Poller Implementation]
  ↓
[Issue 05: Queue Worker & Alerts]
  ↓
[Issue 06: Basic Dashboard UI]
```

---

## 2. 各Issueの詳細設計

### Issue 03: フロントエンド環境の構築および観測所Seederの実装

Inertia + React + Vite + Tailwind CSS を実際に動作させるためのフロントエンド設定ファイルを配置し、動作確認のための観測所（関西圏の10箇所）のマスタデータをSeederとして実装します。

- **新規作成ファイル:**
  - `src/vite.config.js` : Vite + React + Laravelプラグインの設定。
  - `src/tailwind.config.js` : Tailwindのコンテンツ探索パス等の設定。
  - `src/postcss.config.js` : CSSプロセッサ設定。
  - `src/resources/views/app.blade.php` : InertiaのエントリポイントとなるBladeテンプレート。
  - `src/resources/js/app.jsx` : React + Inertiaのフロントエンド側初期化スクリプト。
  - `src/database/seeders/StationSeeder.php` : 関西圏の10観測所（例：淀川水系、大和川水系など）のマスタデータを投入するSeeder。緯度・経度、注意水位・警戒水位の初期値を設定。

---

### Issue 04: Poller (データ収集)の実装

定期実行されて外部API（またはモック）から水位・気象データを取得し、JSONフォーマットに正規化した上でAWS SQSにメッセージを送信する処理を実装します。

- **新規作成ファイル:**
  - `src/app/Console/Commands/WaterLevelPoller.php` : SQSに水位データを送信するためのArtisanコマンド。
  - `src/app/Console/Commands/WeatherPoller.php` : SQSに気象データを送信するためのArtisanコマンド。
  - `src/app/Services/SqsQueueService.php` : AWS SDKを利用し、SQSへのイベント送信を担当する共通サービス。

---

### Issue 05: Queue Worker (非同期処理・アラート判定)の実装

SQSから受信したメッセージを処理するJobと、閾値を超えた場合にアラートレコードを作成しSES（メール）で管理者に通知するロジックを実装します。

- **新規作成ファイル:**
  - `src/app/Jobs/ProcessWaterLevelEvent.php` : SQSからトリガーされ、水位データを検証・保存するジョブ。
  - `src/app/Jobs/ProcessWeatherEvent.php` : 気象データを検証・保存するジョブ。
  - `src/app/Mail/AlertNotification.php` : 注意/警戒水位超過時の管理者向けメール定義（SESでの送信を想定）。

---

### Issue 06: ダッシュボード基本画面の実装

Inertia.js を利用して、バックエンドからデータを取得し、Reactでダッシュボードの基本画面を描画します。

- **新規作成ファイル / 変更ファイル:**
  - `src/app/Http/Controllers/DashboardController.php` (新規) : 観測所一覧、詳細、アラート履歴のデータを供給するコントローラ。
  - `src/routes/web.php` (変更) : ダッシュボード関連ルートの定義。
  - `src/resources/js/Pages/Dashboard.jsx` (新規) : 観測所一覧（ステータスバッジ、現在水位、降水量）を表示する親コンポーネント。
  - `src/resources/js/Pages/StationDetail.jsx` (新規) : 選択された観測所の詳細情報と直近の履歴テーブルを表示するコンポーネント。
  - `src/resources/js/Pages/AlertHistory.jsx` (新規) : 発生したアラートの履歴を一覧表示するコンポーネント。

---

## 3. 検討事項（Open Questions）

開発を円滑に進めるため、データソース（国交省・気象庁API）の扱いに関して以下の方針をご相談させてください。

### ① 国交省水位データの取得方法について
国際的な公開APIが存在せず、有償またはスクレイピング規制があるため、以下のいずれかで実装することを提案します。
- **案A (推奨):** Pollerコマンド内で、関西圏10観測所の現在時刻に応じたリアルタイム風のモック水位データを自動生成してSQSに投入する。
- **案B:** ローカル環境等でモックAPIサーバー（あるいはLaravelアプリ内の一エンドポイント）を構築し、PollerがそれをHTTP GETしてデータを取得する。

### ② 気象データの取得方法について
気象庁の非公式APIは動作が不安定であるため、以下のいずれかを提案します。
- **案A (推奨):** 国交省水位データと同様に、Poller内で関西圏10観測所に紐づけたモックの降水量・気温データを生成する。
- **案B:** 無料かつ登録不要の [Open-Meteo API](https://open-meteo.com/) を利用し、観測所の緯度・経度（lat/lng）を基にリアルタイムの気象データをHTTP GETで取得する。
