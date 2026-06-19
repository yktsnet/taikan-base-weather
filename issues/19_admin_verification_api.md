## 検証モード用バックエンド API の実装
id: 19
skill: pr-workflow
branch-slug: admin-verification-api
github_issue:
status: close
type: feat
対象: src/app/Services/SqsQueueService.php (変更), src/app/Http/Controllers/Admin/VerificationController.php (新規), src/routes/web.php (変更)
内容: 検証画面（管理者用パネル）から負荷テストをトリガーし、キューの滞留状況を監視するためのバックエンド API を実装します。
確認: 
  - 構文エラーがなく、変更/追加された PHP ファイルの `php -l` が通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/19_admin-verification-api_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. SqsQueueService の拡張 (キュー属性の取得)
- **`src/app/Services/SqsQueueService.php`** [MODIFY]:
  - `getQueueAttributes(string $queueUrl)` メソッド（または個別のゲッター）を追加します。
  - AWS SQS API の `GetQueueAttributes` を呼び出し、以下の属性を取得して返します。
    - `ApproximateNumberOfMessages`（現在キューに滞留している未処理メッセージの概算件数）
    - `ApproximateNumberOfMessagesNotVisible`（現在処理中で不可視になっているメッセージの概算件数）
  - 例外発生時はログを出力し、件数を 0 として安全にハンドリングできるようにします。

### 2. 検証用コントローラーの作成
- **`src/app/Http/Controllers/Admin/VerificationController.php`** [NEW]:
  - 以下のエンドポイントに対応するメソッドを実装します。
  - **`POST /admin/api/load-test` (loadTest)**:
    - 負荷テスト用データ投入をトリガーします。
    - バックエンドで `Artisan::call('app:load-test-poller', ['--count' => 1000])`（または引数で件数を指定）を非同期またはプロセスとして実行します。
    - 実行制限「dockerコマンドの実行禁止」があるため、ArtisanコマンドをPHPプロセス経由でバックグラウンド実行する形式（`shell_exec` 等で `php artisan app:load-test-poller > /dev/null 2>&1 &` など）にします。
    - 実行成功レスポンス（`['status' => 'success', 'message' => 'Load test triggered.']`）を返します。
  - **`GET /admin/api/metrics` (getMetrics)**:
    - 現在の SQS キューの滞留メッセージ件数、および直近のDBレコード書き込み件数を取得して返します。
    - `SqsQueueService` を用いて、メインキューの `ApproximateNumberOfMessages` と `ApproximateNumberOfMessagesNotVisible` を取得。
    - 水位データ（`WaterLevel`）および気象データ（`WeatherRecord`）の直近5分間などの書き込み件数を集計して含めます。
    - レスポンス例:
      ```json
      {
        "water_queue": {
          "pending": 120,
          "in_flight": 10
        },
        "weather_queue": {
          "pending": 120,
          "in_flight": 10
        },
        "db_records": {
          "water_levels_count_5m": 150,
          "weather_records_count_5m": 50
        }
      }
      ```

### 3. API ルーティングの追加
- **`src/routes/web.php`** [MODIFY]:
  - 管理者保護ルートグループ (`Route::middleware('auth')->prefix('admin')->group(...)`) の中に、上記コントローラーのルートを追加します。
  - `POST /admin/api/load-test` -> `VerificationController@loadTest`
  - `GET /admin/api/metrics` -> `VerificationController@getMetrics`
