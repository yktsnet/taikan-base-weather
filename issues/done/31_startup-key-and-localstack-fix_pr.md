## 変更内容

APP_KEY 未生成によるセッション/CSRF 不安定、および LocalStack init スクリプトの JSON エスケープバグにより S3 バケットが作成されない問題の修正。

### APP_KEY 自動生成
- `docker-compose.yml` の app startup command に `.env` の自動生成と条件付き `key:generate` を追加
- `.env` が存在しない場合、`.env.docker` からコピーして作成
- `APP_KEY` が空の場合のみ `php artisan key:generate --force` を実行（既存キーは保持）

### LocalStack init スクリプト修正
- `--attributes` パラメータの `RedrivePolicy` JSON を正しいエスケープ形式に修正（shorthand → JSON 形式）
- S3 バケット作成を SQS キュー作成より前に移動し、SQS エラー時の巻き添え防止

## 静的確認結果

PHP ファイルの変更なし。変更対象:

```
docker-compose.yml
docker/localstack/init/ready.d/01-init-resources.sh
```

## 検証手順

1. 既存のコンテナ・ボリュームを削除して初回起動を再現:
   ```bash
   docker compose down -v
   rm -f src/.env
   docker compose up -d --build
   ```
2. app コンテナのログで以下を確認:
   - `.env.docker` → `.env` のコピーが実行されていること
   - `key:generate` が実行されていること（初回のみ）
   - migrate / seed が成功していること
3. `src/.env` を確認し、`APP_KEY=base64:...` が設定されていること:
   ```bash
   grep APP_KEY src/.env
   ```
4. コンテナ再起動で APP_KEY が変わらないこと:
   ```bash
   docker compose restart app
   grep APP_KEY src/.env  # 同じ値のまま
   ```
5. LocalStack の SQS キュー・S3 バケットが作成されていること:
   ```bash
   docker exec kawa-watch-localstack awslocal sqs list-queues --region ap-northeast-1
   docker exec kawa-watch-localstack awslocal s3 ls
   ```
6. ブラウザで `http://localhost:8093` にアクセスし:
   - ログインが安定して動作すること
   - S3 アーカイブ一覧が正常に表示されること
