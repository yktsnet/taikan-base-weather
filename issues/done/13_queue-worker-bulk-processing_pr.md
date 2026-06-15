## Queue Workerのバルク処理化によるDB I/O負荷低減
id: 13
skill: pr-workflow
branch-slug: queue-worker-bulk-processing
status: open
type: feat
対象: src/app/Console/Commands/BulkQueueWorker.php (新規), src/app/Services/SqsQueueService.php (変更), src/app/Jobs/ProcessWaterLevelEvent.php (変更), src/app/Jobs/ProcessWeatherEvent.php (変更)
内容: 先ほど実装した負荷テスト用Pollerコマンドなどによる大量データ受信に備え、SQSからメッセージをバッチで取得し、データベースへの保存をバルクインサート（一括保存）に変更することでDBへのI/O負荷を低減する。
