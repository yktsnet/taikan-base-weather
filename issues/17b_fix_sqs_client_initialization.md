## SQSクライアント初期化設定の修正によるメッセージフロー疎通エラーの解消
id: 17b
skill: pr-workflow
branch-slug: fix-sqs-client-initialization
github_issue:
status: close
type: fix
対象: src/app/Services/SqsQueueService.php
内容: `SqsQueueService.php` のコンストラクタ内で、AWS `SqsClient` を初期化する際に `endpoint` と `credentials`（認証情報）が正しく渡されるように修正し、LocalStack（ローカルSQS環境）へのメッセージ送受信が正常に行われるようにする。
確認:
  - `php artisan app:poll-water-level` がエラーなしで実行され、LocalStack の SQS にメッセージが送信できること。
  - `php artisan app:poll-weather` がエラーなしで実行され、LocalStack の SQS にメッセージが送信できること。
  - `php artisan app:bulk-queue-worker --once` を実行した際、SQSからメッセージが受信・処理され、データベースにデータが反映されること。
  - 修正ファイル `src/app/Services/SqsQueueService.php` について、`php -l` によるシンタックスエラーがないこと。

---

## 修正仕様

### 1. 修正対象ファイル
- `src/app/Services/SqsQueueService.php`

### 2. 修正内容
コンストラクタ内で `SqsClient` を初期化する際、環境変数 `AWS_ENDPOINT` が存在する場合は `endpoint` パラメータを設定します。また、`AWS_ACCESS_KEY_ID` と `AWS_SECRET_ACCESS_KEY` が設定されている場合は `credentials` パラメータを設定します。

```php
    public function __construct()
    {
        $config = [
            'region' => config('services.sqs.region', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
            'version' => 'latest',
        ];

        // LocalStack などのカスタムエンドポイントが指定されている場合は設定
        if (env('AWS_ENDPOINT')) {
            $config['endpoint'] = env('AWS_ENDPOINT');
        }

        // 認証情報が指定されている場合は設定
        if (env('AWS_ACCESS_KEY_ID') && env('AWS_SECRET_ACCESS_KEY')) {
            $config['credentials'] = [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ];
        }

        $this->client = new SqsClient($config);
    }
```

### 3. 検証手順
1. ローカル開発環境で、LocalStack が起動していることを確認。
2. ポーリングを実行し、SQSメッセージの送信が成功することを確認する。
   ```bash
   php artisan app:poll-water-level
   php artisan app:poll-weather
   ```
3. バルクワーカーを実行し、SQSから取得されたメッセージがDBに反映されることを確認する。
   ```bash
   php artisan app:bulk-queue-worker --once
   ```
