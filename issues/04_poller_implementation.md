## Poller (データ収集)の実装
id: 04
skill: pr-workflow
branch-slug: poller-implementation
github_issue:
status: close
type: feat
対象: src/app/Console/Commands/WaterLevelPoller.php (新規), src/app/Console/Commands/WeatherPoller.php (新規), src/app/Services/SqsQueueService.php (新規)
内容: 定期実行されてモックAPI（または現在時刻に応じたモックデータ生成）から水位・気象データを取得し、JSONフォーマットに正規化した上でAWS SQSにメッセージを送信する処理の実装。
確認: php -l による構文チェック、作成されたファイルパスの整合性の目視確認。
---
## 実装仕様

### 1. SQS送信共通サービス
- **`src/app/Services/SqsQueueService.php`**:
  - AWS SDK (`Aws\Sqs\SqsClient`) を利用して、正規化されたイベントデータをAWS SQSキューに送信する共通クラス。
  - 送信時の例外ハンドリングやログ出力を適切に行う。

### 2. 水位データ収集コマンド
- **`src/app/Console/Commands/WaterLevelPoller.php`**:
  - Artisanコマンド `app:poll-water-level` として実装する。
  - `stations` テーブルから有効な観測所コードを取得する。
  - 各観測所について、現在時刻に対応したモック水位データ（潮位や降雨トレンドに基づき多少変動するリアルタイム風のデータ）を生成する。
  - 生成データを以下の共通JSONフォーマットに正規化し、`SqsQueueService` 経由でSQSの水位データ用キューに送信する。
    ```json
    {
      "station_code": "ST001",
      "observed_at": "2026-06-09 14:00:00",
      "level_m": 1.25
    }
    ```

### 3. 気象データ収集コマンド
- **`src/app/Console/Commands/WeatherPoller.php`**:
  - Artisanコマンド `app:poll-weather` として実装する。
  - `stations` テーブルの観測所情報をループ処理する。
  - 各観測所の緯度・経度を元に、モックの気温・降水量データ（あるいは無料のOpen-Meteo API等から取得したデータ）を生成する。
  - 生成データを以下の共通JSONフォーマットに正規化し、`SqsQueueService` 経由でSQSの気象データ用キューに送信する。
    ```json
    {
      "station_code": "ST001",
      "observed_at": "2026-06-09 14:00:00",
      "precipitation_mm": 0.0,
      "temperature_c": 22.5
    }
    ```
