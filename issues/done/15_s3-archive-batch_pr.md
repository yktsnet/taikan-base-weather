## 水位データのCSV化とLocalStack S3保存バッチの実装

### 内容
前日の水位データを日次バッチで自動CSV化し、LocalStackのS3バケットへアップロードするArtisanコマンドを実装しました。将来的なS3ダウンロード機能のバックエンド基盤となります。

### 変更点
- `ArchiveWaterLevelToS3` コマンドを追加。`water_levels` と `stations` から前日分（または指定日）のデータを取得してCSV形式に整形。
- S3 アップロードのため `league/flysystem-aws-s3-v3` を追加。
- `filesystems.php` で参照されている `AWS_BUCKET` と `AWS_ENDPOINT` 等の LocalStack 設定を `.env.example` と `.env.docker` に追加。

### 確認
- `php -l` を用いて構文エラーがないことを確認。
- `league/flysystem-aws-s3-v3` パッケージが利用できることを確認。
- DBからデータ取得し、指定パス (`water-levels/YYYY/MM/water_levels_YYYYMMDD.csv`) にアップロードされる動作の疎通を確認。
