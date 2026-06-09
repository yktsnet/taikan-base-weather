## Queue Worker (非同期処理・アラート判定)の実装
id: 05
skill: pr-workflow
branch-slug: queue-worker
github_issue:
status: close
type: feat
対象: src/app/Jobs/ProcessWaterLevelEvent.php (新規), src/app/Jobs/ProcessWeatherEvent.php (新規), src/app/Mail/AlertNotification.php (新規), issues/done/05_queue-worker_pr.md (新規)
内容: AWS SQSからデータを受信してデータベースに保存し、水位が閾値を超えた場合にアラート登録とSESによる管理者向け警告メール通知を行うロジックの実装。
確認: php -l による構文チェック、作成されたファイルパスの整合性の目視確認。
---
## 実装仕様

### 1. 水位イベント処理ジョブ
- **`src/app/Jobs/ProcessWaterLevelEvent.php`**:
  - SQSキューのメッセージを処理するQueue Job。
  - 受信したJSONデータから `station_code` をキーに `stations` テーブルを検索し、`station_id` を取得する。
  - 受信した水位 `level_m` に基づき、以下のルールで `alert_status`（'normal', 'caution', 'warning', 'danger'）を判定する。
    - 水位が `danger_level` 以上の場合: `danger`
    - 水位が `warning_level` 以上、かつ `danger_level` 未満の場合: `warning`
    - 水位が `warning_level * 0.8` 以上、かつ `warning_level` 未満の場合: `caution`
    - それ以外: `normal`
  - 判定したステータスとともに、`water_levels` テーブルにレコードを保存する。
  - ステータスが `caution`, `warning`, `danger` のいずれかになった場合、新規アラートとして `alerts` テーブルにレコードを挿入する。
  - アラート登録後、管理者向けの通知メールジョブ `AlertNotification` をディスパッチする。メール送信完了後、`alerts` テーブルの `notified` フラグを `true` に更新する。

### 2. 気象イベント処理ジョブ
- **`src/app/Jobs/ProcessWeatherEvent.php`**:
  - SQSキューの気象イベントメッセージを処理するジョブ。
  - 水位と同様に観測所を特定し、`weather_records` テーブルに降水量（`precipitation_mm`）、気温（`temperature_c`）、観測時間（`observed_at`）を保存する。

### 3. アラート通知メール
- **`src/app/Mail/AlertNotification.php`**:
  - 閾値超過を管理者に通知する `Mailable` クラス。
  - メール本文には、対象の観測所名、河川名、都道府県、観測時刻、水位、および判定された警戒レベル（caution / warning / danger）をわかりやすく記載する。
