## 変更内容
- `SqsQueueService.php` に AWS SQS API からキュー属性（`ApproximateNumberOfMessages`, `ApproximateNumberOfMessagesNotVisible`）を取得し、未処理および処理中のメッセージ件数を返却する `getQueueAttributes` メソッドを追加しました。
- 管理者検証用コントローラー `VerificationController.php` を新規作成しました。
  - 負荷テスト投入API (`POST /admin/api/load-test`): `app:load-test-poller` Artisan コマンドをバックグラウンドプロセスとして非同期起動する処理を実装。
  - メトリクス取得API (`GET /admin/api/metrics`): SQS の現在の滞留・処理中メッセージ数および、直近5分間にDBへ書き込まれた水位・気象データレコードの合計件数を集計して JSON 返却する処理を実装。
- `web.php` の管理者専用ルートグループ内に、上記の負荷テスト投入およびメトリクス取得用の API ルートを追加定義しました。

## 静的確認結果
- 変更・新規作成したすべての PHP ファイル（`SqsQueueService.php`, `VerificationController.php`, `web.php`）に対して `php -l` による構文チェックを実行し、エラーがないことを確認しました。

## 検証手順
user様は以下の手順で API の動作をご確認いただけます。

1. ローカル環境で LocalStack (SQS) が起動していることを確認します。
2. 管理者ユーザーでログインした状態で、メトリクス取得 API にアクセスし、現在のキュー滞留数（通常は 0 件）とDB書き込み件数が JSON で返却されることを確認します。
   ```bash
   # URL例 (GET)
   http://localhost:8000/admin/api/metrics
   ```
3. 負荷テスト投入 API (POST) を実行し、バックグラウンドで負荷テストデータが SQS に投入され、再度メトリクス取得 API を叩いた際に滞留件数が増加すること、およびワーカー処理に伴いDBレコード数が増加することを確認します。
   ```bash
   # APIリクエスト例 (POST - ログインCookie等を伴う必要があります)
   curl -X POST http://localhost:8000/admin/api/load-test -d "count=1000"
   ```
