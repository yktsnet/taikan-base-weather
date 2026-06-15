## Queue Workerのバルク処理化によるDB I/O負荷低減
id: 13
skill: pr-workflow
branch-slug: queue-worker-bulk-processing
github_issue:
status: open
type: feat
対象: src/app/Console/Commands/BulkQueueWorker.php (新規), src/app/Services/SqsQueueService.php (変更), src/app/Jobs/ProcessWaterLevelEvent.php (変更), src/app/Jobs/ProcessWeatherEvent.php (変更)
内容: 先ほど実装した負荷テスト用Pollerコマンドなどによる大量データ受信に備え、SQSからメッセージをバッチで取得し、データベースへの保存をバルクインサート（一括保存）に変更することでDBへのI/O負荷を低減する。
確認: 
  - 構文エラーがないこと、および正常にビルド・テストが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/13_queue-worker-bulk-processing_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. SQSバッチ受信機能の追加 (`SqsQueueService.php`)
- `SqsQueueService` に、SQSから複数（最大10件）のメッセージを一括で受信するメソッド `receiveMessageBatch(string $queueUrl, int $maxMessages = 10): array` を追加する。
- 受信したメッセージの `ReceiptHandle` や `Body` を含む配列を返却するようにする。
- メッセージ処理完了後に、まとめてメッセージを削除するための `deleteMessageBatch(string $queueUrl, array $receiptHandles): bool` も追加する。

### 2. バルク処理用カスタムキューワーカーの作成 (`BulkQueueWorker.php`)
- 標準の `queue:work` は1件ずつ処理するため、バッチ受信したメッセージ群をまとめて高速処理するArtisanコマンド `app:bulk-queue-worker` を新規作成する。
- このコマンドは以下の処理をループ実行する：
  1. 水位および気象データのSQSキューからメッセージをバッチ（最大10件またはそれ以上）で受信する。
  2. 受信したイベントデータを解析し、観測所（`stations`）情報を考慮した上で、`water_levels` および `weather_records` テーブルへの**バルクインサート（一括保存）**用データを組み立てる。
  3. `WaterLevel::insert()` や `WeatherRecord::insert()` などの Eloquent/Query Builder を利用し、1回のクエリでまとめてデータベースにインサートする。
  4. 水位超過等によるアラート判定処理を行い、警告が必要な場合は `alerts` テーブルにバルクインサートする。また、メール通知ジョブを非同期でディスパッチする。
  5. データベース保存が正常に完了したら、SQSからメッセージを一括削除（`deleteMessageBatch`）する。
