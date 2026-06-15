## 並行実行時のデータ整合性とデッドロック回避の実装

Queue Workerがマルチプロセスで並行起動した際に、同一観測所のデータ書き込みやアラート超過判定において、データの不整合やDBデッドロックが発生するのを防ぐために、トランザクションとDBロック（行ロック）を実装しました。

### 変更内容
1. `ProcessWaterLevelEvent.php` にて、一連のDB処理（観測所データの検証、`water_levels` への一括挿入、`alerts` へのアラート超過判定と挿入）を `DB::transaction()` でラップしました。
2. 同様に `ProcessWaterLevelEvent.php` において、`Station` のデータを取得する際に `lockForUpdate()` を適用し、並行処理によるデッドロックやアラートの重複作成を防止しました。また、メール送信の処理をトランザクション外に出すことで、不要なロックを保持することを回避しました。
3. `ProcessWeatherEvent.php` においても、`weather_records` への一括挿入処理を `DB::transaction()` でラップし、`Station` データの取得時に `lockForUpdate()` を適用しデッドロックを防ぐように修正しました。
