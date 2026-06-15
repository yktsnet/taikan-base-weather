## 水位データのCSV化とLocalStack S3保存バッチの実装
id: 15
skill: pr-workflow
branch-slug: s3-archive-batch
github_issue:
status: open
type: feat
対象: src/app/Console/Commands/ArchiveWaterLevelToS3.php (新規), src/config/filesystems.php (変更), src/.env.example (変更), src/.env.docker (変更)
内容: 前日の水位データを日次バッチで自動CSV化し、LocalStackのS3バケットへアップロードするArtisanコマンドを実装する。将来的なS3ダウンロード機能のバックエンド基盤となる。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容 to `issues/done/15_s3-archive-batch_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. LocalStack S3 接続設定の追加 (`filesystems.php`, `.env.*`)
- Laravelの `Storage` ファサードの `s3` ディスク設定、またはカスタムディスク設定を利用して、LocalStackのS3（endpoint指定などを含む）に接続できるように `config/filesystems.php` を設定・調整する。
- S3のバケット名やLocalStackのエンドポイントを指定するための環境変数（例：`AWS_S3_ENDPOINT`、`AWS_BUCKET` など）を `src/.env.example` および `src/.env.docker` に追加する。

### 2. CSVアーカイブコマンドの作成 (`ArchiveWaterLevelToS3.php`)
- Artisanコマンド `app:archive-water-level {--date= : アーカイブ対象日(YYYY-MM-DD)。デフォルトは前日}` を新規作成する。
- 処理フロー:
  1. 指定された日付（指定がなければコマンド実行時の「前日」）の `water_levels` データをDBから全件取得する。その際、関連する `stations` のコードや名前も同時に取得する。
  2. データをCSV形式（ヘッダー: `observed_at, station_code, station_name, river_name, level_m, alert_status`）の文字列へ整形する。
  3. 整形したデータを `water-levels/YYYY/MM/water_levels_YYYYMMDD.csv` のような整理されたパスでLocalStackのS3バケットへアップロード（保存）する。
  4. アップロード完了またはエラーログを適切に出力する。
