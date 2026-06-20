Closes #4

## 変更内容

B パターン（オンプレ上で LocalStack をシミュレーション常駐）運用に向け、LocalStack 起動時に SQS キュー・S3 バケットを自動作成する初期化フックを追加し、env を実リソースと整合させる。

- **`docker/localstack/init/ready.d/01-init-resources.sh`（新規）**: LocalStack 3.x の init フック。`awslocal` で DLQ (`kawa-watch-raw-events-dlq`)、メインキュー 2 本 (`kawa-watch-water-level`, `kawa-watch-weather`、RedrivePolicy 付き)、S3 バケット (`kawa-watch-bucket`) を冪等に作成する。
- **`docker-compose.yml`**: localstack サービスに init フックのボリュームマウント (`./docker/localstack/init/ready.d:/etc/localstack/init/ready.d`) と `SERVICES=sqs,s3` を追加。
- **`src/.env.docker`**: poller が参照する `AWS_SQS_WATER_LEVEL_QUEUE_URL` / `AWS_SQS_WEATHER_QUEUE_URL` を追記。

## 静的確認結果

- `bash -n docker/localstack/init/ready.d/01-init-resources.sh` → 構文エラーなし
- `yq e '.' docker-compose.yml` → YAML 構造正常
- .env.docker の 3 つの SQS URL（water / weather / DLQ）が初期化スクリプトで作成するキュー名と一致することを確認済み

```
変更ファイル:
docker-compose.yml
docker/localstack/init/ready.d/01-init-resources.sh (新規)
src/.env.docker
```

## 検証手順

1. `docker compose down && docker compose up -d` でコンテナを再起動する。
2. LocalStack コンテナのログを確認し、init スクリプトが正常に完了していることを確認する。
   ```bash
   docker compose logs localstack | grep "LocalStack init"
   ```
3. SQS キューが作成されていることを確認する。
   ```bash
   aws --endpoint-url=http://localhost:4567 sqs list-queues --region ap-northeast-1
   ```
4. S3 バケットが作成されていることを確認する。
   ```bash
   aws --endpoint-url=http://localhost:4567 s3 ls --region ap-northeast-1
   ```
5. scheduler / worker コンテナが正常に起動し、SQS に接続できていることをログで確認する。
