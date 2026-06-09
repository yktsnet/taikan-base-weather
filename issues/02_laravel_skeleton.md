## Laravel 11 スケルトンおよびデータモデルの構築
id: 02
skill: pr-workflow
branch-slug: laravel-skeleton
github_issue:
status: close
type: feat
対象: src/composer.json (新規), src/package.json (新規), src/database/migrations/2026_06_09_000001_create_stations_table.php (新規), src/database/migrations/2026_06_09_000002_create_water_levels_table.php (新規), src/database/migrations/2026_06_09_000003_create_weather_records_table.php (新規), src/database/migrations/2026_06_09_000004_create_alerts_table.php (新規)
内容: Laravel 11 + Inertia + Reactの依存定義、およびPLAN.mdに基づくデータモデル（4テーブル）のマイグレーション作成
確認: php -l によるマイグレーションファイルの構文チェック（Jules環境での実行可能な範囲）、リレーションの整合性目視確認

---

## 実装仕様

環境構築コマンドを実行できない制約に基づき、主要なパッケージ定義とデータベーススキーマ（マイグレーション）のコード配置を直接行う。

### 1. 依存関係の定義
- **`src/composer.json`**:
  - Laravel 11の基本構成。
  - SQS連携およびAWS SDK（`aws/aws-sdk-php`）の依存関係を付記。
  - Inertia Laravel用パッケージ（`inertiajs/inertia-laravel`）を含める。
- **`src/package.json`**:
  - React、ReactDOM、Inertia React（`@inertiajs/react`）、Vite、Tailwind CSS等のフロントエンド依存関係を定義。

### 2. マイグレーション構造 (PLAN.mdのデータモデル準拠)

- **`create_stations_table`** (観測所マスタ)
  - `id` (bigint PK)
  - `code` (string, unique): 国交省観測所コード
  - `name` (string): 観測所名
  - `river_name` (string): 河川名
  - `prefecture` (string): 都道府県
  - `lat`, `lng` (decimal, 10, 7): 位置情報
  - `warning_level`, `danger_level` (decimal, 5, 2, nullable): 閾値水位 (m)
  - `timestamps`

- **`create_water_levels_table`** (水位記録)
  - `id` (bigint PK)
  - `station_id` (foreignId): stations.id 参照 (onDelete cascade)
  - `observed_at` (timestamp): 観測時刻
  - `level_m` (decimal, 5, 2): 水位 (m)
  - `alert_status` (enum): 'normal', 'caution', 'warning', 'danger'
  - インデックス: `[station_id, observed_at]` の複合
  - `timestamps`

- **`create_weather_records_table`** (気象記録)
  - `id` (bigint PK)
  - `station_id` (foreignId): stations.id 参照 (onDelete cascade)
  - `observed_at` (timestamp)
  - `precipitation_mm` (decimal, 5, 1): 降水量 (mm/h)
  - `temperature_c` (decimal, 4, 1): 気温 (℃)
  - インデックス: `[station_id, observed_at]` の複合
  - `timestamps`

- **`create_alerts_table`** (アラート履歴)
  - `id` (bigint PK)
  - `station_id` (foreignId): stations.id 参照
  - `triggered_at` (timestamp)
  - `level` (enum): 'caution', 'warning', 'danger'
  - `level_m` (decimal, 5, 2): トリガー時の水位
  - `notified` (boolean, default false): SES送信済みフラグ
  - `timestamps`

## 成果物確認（Agent実行）
- 生成したPHP（マイグレーション）ファイルに対する `php -l` チェックの実施。
- コマンド実行制限を遵守。`composer install` や `php artisan migrate` は実行しない。
