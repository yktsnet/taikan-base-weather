## SQSメッセージの送信・デキュー失敗バグの調査と修正
id: 17a
skill: pr-workflow
branch-slug: sqs-worker-fix
github_issue:
status: open
type: fix
対象: src/app/Services/SqsQueueService.php (変更), src/app/Console/Commands/WaterLevelPoller.php (変更), src/app/Console/Commands/WeatherPoller.php (変更), src/app/Console/Commands/BulkQueueWorker.php (変更)
内容: `app:bulk-queue-worker --once` コマンドを実行しても、SQS（LocalStack）上のメッセージが一切デキュー（処理）されない問題を調査・修正する。また、デバッグを容易にするため、各ポーリング・ワーカーコマンドの接続・エラーログ出力を強化する。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/17a_sqs-worker-fix_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 調査および実装仕様

### 1. 【原因推測】AWS SQSクライアントの初期化設定の確認 (`SqsQueueService.php`)
- `SqsQueueService.php` のコンストラクタで `SqsClient` を初期化する際、環境変数 `AWS_ENDPOINT`（LocalStack用の `http://localhost:4566`）がクライアント構成に渡されていないため、ローカルのLocalStackではなく本物のAWS SQSへアクセスを試みて通信が失敗している可能性が極めて高い。
- コンストラクタを修正し、`AWS_ENDPOINT` や `AWS_USE_PATH_STYLE_ENDPOINT` の環境変数が設定されている場合は、これらを `SqsClient` の初期化設定に適切にマージするように修正すること。
  - 例:
    ```php
    $config = [
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
        'version' => 'latest',
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID', 'test'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
        ]
    ];
    if (env('AWS_ENDPOINT')) {
        $config['endpoint'] = env('AWS_ENDPOINT');
    }
    $this->client = new \Aws\Sqs\SqsClient($config);
    ```

### 2. コマンド標準出力とデバッグログの強化
- `WaterLevelPoller.php` / `WeatherPoller.php`:
  - `sendMessage` や `sendMessageBatch` を呼び出す際、送信結果の成否（`true`/`false`）だけでなく、**例外エラーメッセージや詳細（`$e->getMessage()`）をキャッチして `$this->error()` でコンソールに出力**するように改修する。これにより、送信失敗時に何が起きているか（認証エラー、通信エラー等）を一目でわかるようにする。
- `BulkQueueWorker.php`:
  - 起動時に、接続を試みている SQS キューのURL（`$waterQueueUrl`, `$weatherQueueUrl`）を `$this->info()` でコンソールに表示するデバッグログを追加する。
