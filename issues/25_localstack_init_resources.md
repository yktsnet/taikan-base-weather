## LocalStack 本番常駐化（起動時リソース自動作成）

id: 25
skill: pr-workflow
branch-slug: localstack-init-resources
github_issue: 4
status: open
type: feat
対象: docker/localstack/init/ready.d/01-init-resources.sh (新規), docker-compose.yml, src/.env.docker
内容: B パターン（オンプレ SV6 上で LocalStack をシミュレーション常駐）運用に向け、LocalStack 起動時に SQS キュー・S3 バケットを自動作成する初期化フックを追加し、env を実リソースと整合させる。
確認: 初期化スクリプトを `bash -n docker/localstack/init/ready.d/01-init-resources.sh` で構文確認。docker-compose.yml の YAML 構造を目視（必要なら `nix-shell -p yq-go --run "yq e '.' docker-compose.yml"`、pip 禁止）。.env.docker の3つの SQS URL（water / weather / DLQ）が初期化スクリプトで作成するキュー名と一致することを目視確認。

---

## 背景

LocalStack は `kawa-watch-localstack-data` ボリュームに常駐済み（Issue 23）だが、SQS/S3 リソースを作成する仕組みが無い。コンテナ再起動のたびに揃うよう、LocalStack 3.x の init フック（`/etc/localstack/init/ready.d/*.sh`）で冪等に再作成する。永続化フラグには依存しない（毎起動で作り直す方が堅牢）。

参照されるリソース（コード調査結果）:
- SQS: `AWS_SQS_WATER_LEVEL_QUEUE_URL`（WaterLevelPoller）、`AWS_SQS_WEATHER_QUEUE_URL`（WeatherPoller）、`AWS_SQS_DLQ_QUEUE_URL`（既存 `kawa-watch-raw-events-dlq`）
- S3: `AWS_BUCKET=kawa-watch-bucket`（VerificationController / ArchiveWaterLevelToS3、`Storage::disk('s3')`）

## 1. docker/localstack/init/ready.d/01-init-resources.sh（新規）

LocalStack 内蔵の `awslocal` で以下を冪等に作成する（既存でもエラーにしない）。account は `000000000000`、region は `ap-northeast-1`。

- DLQ: `kawa-watch-raw-events-dlq`
- メインキュー2本: `kawa-watch-water-level` / `kawa-watch-weather`
  - それぞれ RedrivePolicy で DLQ を deadLetterTargetArn に設定（maxReceiveCount は 5 目安）
- S3 バケット: `kawa-watch-bucket`

実装メモ:
- shebang `#!/bin/bash`。`create-queue` は冪等。`s3 mb` は既存時に失敗するため `|| true` で吸収する。
- DLQ の ARN は作成後に `get-queue-attributes` で取得してメインキューの RedrivePolicy に流し込む。

## 2. docker-compose.yml

`localstack` サービスに init フックをマウントする。
- `volumes` に `./docker/localstack/init/ready.d:/etc/localstack/init/ready.d` を追加。
- `environment` に `SERVICES=sqs,s3` を明示（任意）。

## 3. src/.env.docker

pollers が参照するメインキュー URL を追記し、初期化スクリプトで作成するキュー名と一致させる。
```env
AWS_SQS_WATER_LEVEL_QUEUE_URL=http://localstack:4566/000000000000/kawa-watch-water-level
AWS_SQS_WEATHER_QUEUE_URL=http://localstack:4566/000000000000/kawa-watch-weather
```
※ `AWS_SQS_DLQ_QUEUE_URL` / `SQS_PREFIX` / `AWS_BUCKET` / `AWS_ENDPOINT` は既存のまま維持。

## 実装順序

1 → 2 → 3。スクリプトのキュー名と .env.docker の URL は必ず一致させること。
