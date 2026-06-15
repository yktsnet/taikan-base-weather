# SQSメッセージ送受信失敗に関する調査レポート

## 1. 問題の根本原因

`app:bulk-queue-worker --once` や `app:poll-water-level` コマンドを実行してもSQS（LocalStack）上のメッセージが一切処理されない、あるいは送信されない根本原因は、**`SqsQueueService` クラスにおけるAWS SQSクライアント（`SqsClient`）の初期化設定の不備**です。

現在の実装（`src/app/Services/SqsQueueService.php` のコンストラクタ）では、`SqsClient` の初期化時に `region` と `version` のみが指定されており、**LocalStackへの接続先URLである `endpoint` や、認証情報（`credentials`）が渡されていません。**

このため、AWS SDK for PHP がデフォルトの挙動として実際のAWSクラウド環境（のデフォルトエンドポイント）に接続しようとします。さらに、認証情報が明示されていないため、SDKはEC2インスタンスメタデータサービス（IMDS: `http://169.254.169.254/latest/meta-data/`）へ認証情報の取得を試み、接続タイムアウトを引き起こしエラーとなります。結果として、LocalStackのSQSに対するメッセージ送信・受信が共に失敗しています。

## 2. 調査プロセスとエビデンス

1. **エラー詳細の確認 (PHPスクリプトによるテスト):**
   `SqsQueueService` を利用して直接 LocalStack (`http://localstack:4566/...`) 宛にメッセージを送信する検証スクリプトを作成・実行しました。
   ログ出力 (`laravel.log`) にて以下の致命的なエラーが発生していることを確認しました。

   ```
   [2026-06-15 07:17:41] production.ERROR: Unexpected error sending message to SQS. {"queueUrl":"http://localstack:4566/000000000000/water-level-queue","error":"Error retrieving credentials from the instance profile metadata service. (cURL error 7: Failed to connect to 169.254.169.254 port 80 after 1 ms: Couldn't connect to server (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://169.254.169.254/latest/meta-data/iam/security-credentials/)"}
   ```

2. **ソースコードの確認 (`src/app/Services/SqsQueueService.php`):**
   コンストラクタ内で以下のように `SqsClient` が初期化されていました。

   ```php
   $this->client = new SqsClient([
       'region' => config('services.sqs.region', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
       'version' => 'latest',
   ]);
   ```
   ここで、`.env.docker` に定義されている `AWS_ENDPOINT=http://localstack:4566` や、`AWS_ACCESS_KEY_ID`、`AWS_SECRET_ACCESS_KEY` が一切利用されていないことが判明しました。

3. **コンフィグの確認 (`src/config/services.php`):**
   `config('services.sqs...')` を参照していますが、`services.php` の設定ファイル内に `sqs` キー自体が存在しませんでした。

## 3. 具体的な修正案

この問題を解決するためには、`SqsQueueService` クラスのコンストラクタを修正し、`.env` ファイル等で定義されたエンドポイントと認証情報を `SqsClient` の初期化パラメータに含める必要があります。

### 修正対象ファイル
`src/app/Services/SqsQueueService.php`

### 修正内容（差分案）

`SqsClient` 生成時の配列に `endpoint` と `credentials` を追加します。

```diff
<<<<<<< SEARCH
    public function __construct()
    {
        $this->client = new SqsClient([
            'region' => config('services.sqs.region', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
            'version' => 'latest',
        ]);
    }
=======
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
>>>>>>> REPLACE
```

これにより、ワーカーおよびポーラー実行時にLocalStackのSQSに正しく接続・認証され、メッセージの送受信が正常に行われるようになります。
