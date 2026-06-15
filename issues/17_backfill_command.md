## バックフィル機能（過去データ取得）の実装とアラート通知暴発防止
id: 17
skill: pr-workflow
branch-slug: backfill-command
github_issue:
status: open
type: feat
対象: src/app/Console/Commands/WaterLevelPoller.php (変更), src/app/Console/Commands/WeatherPoller.php (変更), src/app/Jobs/ProcessWaterLevelEvent.php (変更)
内容: 過去の特定期間のデータを取得してSQSへ一括投入する「バックフィル」機能をPollerに追加する。また、過去のデータ処理時に大量のアラートメール通知（SES）が誤送信されるのを防ぐため、SQSイベントへのスキップフラグ追加とJob側での通知抑制ロジックを実装する。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/17_backfill-command_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. Pollerへのバックフィル用引数の追加とイベント設計
- `app:poll-water-level` および `app:poll-weather` コマンドのシグネチャを拡張し、`--start` および `--end` オプションを受け取れるようにする。
  - 例: `app:poll-water-level {--start=} {--end=}`
- 過去の期間指定がある場合：
  - 各APIクライアント（`RiverApiService` / `JmaApiService`）から、指定された期間内の過去データを取得（またはシミュレーション）するように実装する。
  - SQSに送信するイベントデータの配列に、`'skip_notification' => true` というフラグを追加する。

### 2. Job側でのアラートメール通知スキップ制御 (`ProcessWaterLevelEvent.php`)
- `ProcessWaterLevelEvent` の `handle()` 処理で、受信したイベントデータに `'skip_notification' => true` フラグが含まれているか確認する。
- もしフラグが `true` の場合、以下の処理を変更する：
  - 水位レコード（`WaterLevel`）のインサートは通常通り実行する。
  - 水位が警戒値を超えていても、メール通知（`AlertNotification`）のディスパッチ処理はスキップ（抑制）する。
  - ※アラート履歴レコード（`Alert`）のDB保存自体は、過去ログとして残すために実行しても構わないが、メール送信と送信済みアップデートの処理のみをスキップさせる。
