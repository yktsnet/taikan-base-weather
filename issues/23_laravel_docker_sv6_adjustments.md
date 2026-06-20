## Laravel Scheduler and Docker Adjustments for SV6
id: 23
skill: pr-workflow
branch-slug: laravel-docker-sv6-adjustments
github_issue: 1
status: open
type: feat
対象: src/bootstrap/app.php, docker-compose.yml, src/.env.docker
内容: SV6での本番稼働に向けて、Laravelの自動実行スケジュールを定義し、Redis/Mailpitの削除やポート競合回避などのDocker構成調整を行う。
確認: php -l によるシンタックス確認、および docker-compose.yml の構文妥当性確認

---

### 1. src/bootstrap/app.php
`withSchedule` メソッドを追加し、5分おきに以下の poller コマンドを実行するようにスケジュールを設定する。
- `app:poll-water-level`
- `app:poll-weather`

```php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('app:poll-water-level')->everyFiveMinutes();
    $schedule->command('app:poll-weather')->everyFiveMinutes();
})
```
※ `Illuminate\Console\Scheduling\Schedule` のインポート文が正しく処理されることを確認すること。

### 2. docker-compose.yml
SV6上で既に稼働中の他サービス（`order-system-migration` など）との競合を防ぐため、およびリソース節約のための調整を行う。

- **不要サービスの削除**:
  - `redis` および `mail` (mailpit) サービスを削除する。
  - `app` の `depends_on` から `redis` を削除する。
- **ポート競合の回避**:
  - `app` の公開ポート（`ports`）を `8093:8000` に変更する。
  - `db` の公開ポート `3306:3306` を、ホストのデフォルトMySQLとの競合を避けるため `127.0.0.1:3307:3306` に変更するか、ホストから直接DB接続しない場合はポートマッピング自体を削除する。
  - `localstack` の公開ポート `4566:4566` を、他サービスが使用中の `4566` との競合を避けるため `127.0.0.1:4567:4566` に変更する。
- **コンテナ内からLocalStackへの接続**:
  - コンテナ間通信は `http://localstack:4566` で行われるため、ホスト側ポートが `4567` に変わっても影響を受けない。

### 3. src/.env.docker
Redis削除およびリソース節約のため、以下の環境変数を書き換える。
```env
SESSION_DRIVER=file
CACHE_STORE=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```
※ `QUEUE_CONNECTION` は、`app:bulk-queue-worker` 自体が直接 SQS をポーリングして処理する独自実装であるため、Laravelのデフォルトキュー接続は `sync` に変更しRedis依存を排除する。
※ AWS/LocalStack関連の環境変数（`AWS_ENDPOINT`, `SQS_PREFIX`, `AWS_SQS_DLQ_QUEUE_URL` など）はLocalStackを参照し続けるよう維持する。
