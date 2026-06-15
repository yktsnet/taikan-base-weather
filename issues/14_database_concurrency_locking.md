## 並行実行時のデータ整合性とデッドロック回避の実装
id: 14
skill: pr-workflow
branch-slug: database-concurrency-locking
github_issue:
status: close
type: feat
対象: src/app/Jobs/ProcessWaterLevelEvent.php (変更), src/app/Jobs/ProcessWeatherEvent.php (変更)
内容: Queue Workerがマルチプロセスで並行起動した際、同一観測所のデータ書き込みやアラート超過判定において、データの不整合やDBデッドロックが発生するのを防ぐために、トランザクションと適切なDBロック（行ロック）を実装する。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/14_database-concurrency-locking_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. 水位データ処理時のトランザクションと行ロックの適用 (`ProcessWaterLevelEvent.php`)
- 1つのジョブ内で実行される「複数観測所データの検証」「`water_levels` への一括挿入」「`alerts` へのアラート超過判定と挿入」の一連のDB処理を、データベースのトランザクション（`DB::transaction`）内にまとめる。
- 並行起動した別のWorkerプロセスが同時に同じ観測所のデータを処理し、デッドロックやアラートの二重作成が起きるのを防ぐため、対象の観測所を取得するクエリ（`Station::whereIn(...)`）に `lockForUpdate()` 等の排他ロック（行ロック）を適用する。
  - 例: `Station::whereIn('code', $stationCodes)->lockForUpdate()->get()->keyBy('code');`
- アラート判定が正確かつアトミックに行われ、同一観測時刻・同一レベルの重複アラートが作成されないように整合性を担保する。

### 2. 気象データ処理時のトランザクション適用 (`ProcessWeatherEvent.php`)
- `ProcessWeatherEvent` における「`weather_records` への一括挿入」処理についても、整合性確保のためトランザクションと必要に応じたロック処理を適用する。
- 観測所取得（`Station::whereIn(...)`）に対して同様に行ロックを検討し、データ書き込み時のデッドロックを防止する。
