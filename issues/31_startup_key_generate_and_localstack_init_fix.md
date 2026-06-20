## 起動時の APP_KEY 生成漏れ・LocalStack init スクリプトの JSON パースエラー

id: 31
skill: pr-workflow
branch-slug: startup-key-and-localstack-fix
github_issue: 19
status: close
type: fix
対象: docker-compose.yml, docker/localstack/init/ready.d/01-init-resources.sh
内容: APP_KEY 未生成によるセッション/CSRF 不安定、および LocalStack init スクリプトの JSON エスケープバグにより S3 バケットが作成されない問題の修正
確認: docker-compose.yml / init スクリプトの構文確認、実環境での動作確認は user が実施

---

## 症状

1. **ログインが不安定**: 管理者ログインボタンが反応したりしなかったりする。ブラウザの開発者ツールにエラーが出る。
2. **S3 アーカイブ一覧が取得失敗**: ダッシュボードに「アーカイブ一覧の取得に失敗しました」と表示される。

## 根本原因

### APP_KEY 空（ログイン不安定）

`.env.docker` で `APP_KEY=`（空）のままになっている。
`docker-compose.yml` にも `Dockerfile` にも `php artisan key:generate` が無く、一度も自動生成されない。

Laravel は APP_KEY でセッション・CSRF トークンを暗号化するため、空だと:
- セッション暗号化が壊れる
- CSRF トークン検証が不定に失敗/成功する
- ログインフォームの POST が弾かれたり通ったりする

暫定で `docker exec kawa-watch-app php artisan key:generate --force` を手動実行して解消済み。

### LocalStack init スクリプトの JSON パースエラー（S3 バケット未作成）

`docker/localstack/init/ready.d/01-init-resources.sh` の SQS キュー作成で、
`--attributes` に渡す `RedrivePolicy` の JSON がシェルで正しくエスケープされていない:

```
Error parsing parameter '--attributes': Expected: '=', received: '"' for input:
RedrivePolicy={"deadLetterTargetArn":"arn:aws:sqs:...","maxReceiveCount":"5"}
```

`set -euo pipefail` によりスクリプトがここで中断し、後続の S3 バケット作成(`s3 mb`)に到達しない。
結果、`kawa-watch-bucket` が存在せず、アーカイブ一覧 API が失敗する。

暫定で `awslocal s3 mb s3://kawa-watch-bucket --region ap-northeast-1` を手動実行して解消済み。

## 修正方針（案・実装者が最終判断）

### APP_KEY

`docker-compose.yml` の app command に、migrate の前で key:generate を冪等に実行:

```sh
php artisan key:generate --force --no-interaction
```

ただし `key:generate` は `.env` を上書きするため、既に KEY がセットされている場合に再生成しない制御が望ましい。
例: `grep -q '^APP_KEY=$' .env && php artisan key:generate --force` のような条件分岐。

### LocalStack init スクリプト

`--attributes` の JSON をシングルクォートで囲むか、`--cli-input-json` 形式に変更して正しくエスケープする:

```bash
awslocal sqs create-queue \
  --queue-name "${QUEUE_NAME}" \
  --attributes "{\"RedrivePolicy\":\"{\\\"deadLetterTargetArn\\\":\\\"${DLQ_ARN}\\\",\\\"maxReceiveCount\\\":\\\"${MAX_RECEIVE_COUNT}\\\"}\"}" \
  --region "${REGION}"
```

あるいは S3 バケット作成をキュー作成の前に移動し、キューのエラーで S3 が巻き添えにならないようにする（順序変更 + エラー分離）。

## 関連

- Issue 30: migrate/seed の自動実行（マージ済み）。本 Issue は 30 で漏れた初期化ステップ。

## 確認

- コンテナ初回起動で APP_KEY が自動生成されること
- 再起動時に APP_KEY が再生成されない（既存セッションが無効化されない）こと
- LocalStack init で SQS キュー・S3 バケットがすべて作成されること
- ダッシュボードでログインが安定し、S3 アーカイブ一覧が表示されること
- 実環境での確認は user が実施
