## SQSメッセージ送受信失敗に関する調査レポートの作成
id: 17a
skill: pr-workflow
branch-slug: investigate-sqs-message-flow
github_issue:
status: open
type: feat
対象: issues/done/17a_investigate-sqs-message-flow_pr.md (新規)
内容: `app:bulk-queue-worker --once` コマンドを実行してもSQS（LocalStack）上のメッセージが一切処理されない問題について、ソースコード上のAWSクライアント接続設定や、Pollerコマンド送信時のエラー詳細ログを徹底的に調査し、原因および具体的な修正方針をレポート（調査報告書）としてまとめる。コードの修正（ファイル編集）は一切行わないこと。
確認: 
  - プロジェクト内のソースコード（PHPファイルなど）が一切変更・編集されていないこと（`git diff` が空であること）。
  - 調査結果をまとめたレポートが `issues/done/17a_investigate-sqs-message-flow_pr.md` に正常に出力されていること。

---

## 調査仕様

### 1. 調査対象と着眼点
以下の点についてログや挙動を確認し、原因を特定する。
- **Poller側の送信動作の確認**: 
  `php artisan app:poll-water-level` 等を実行した際、SQSへのメッセージ送信（`SqsQueueService::sendMessage`）が実際に成功しているか。成功・失敗時のログがどこに出力されているか。
- **SqsClientの接続先初期化設定**:
  `SqsQueueService.php` において、AWS SQSクライアント（`SqsClient`）を初期化するコンストラクタで、環境変数 `AWS_ENDPOINT`（LocalStackの `http://localhost:4566`）が正しく読み込まれ、クライアント構成に渡されているか。
- **キューURLの解決状況**:
  ポーリング・ワーカーコマンド内で参照している `AWS_SQS_WATER_LEVEL_QUEUE_URL` などの環境変数が、コマンド実行時に正しく読み込めているか。
- **LocalStack内のキューの状態確認**:
  コンテナ内のLocalStackのSQSにメッセージが蓄積されているか（未処理メッセージ数など）。

### 2. 成果物（調査レポート）の記述内容
調査完了後、`issues/done/17a_investigate-sqs-message-flow_pr.md` に以下の内容を含んだレポートを作成すること。
1. **問題の根本原因**: なぜワーカーでメッセージが処理されないのか（送信エラーが起きているのか、接続先が間違っているのかなど、詳細な技術的理由）。
2. **調査プロセスとエビデンス**: 特定に至るまでに確認したログや検証コマンドの実行結果。
3. **具体的な修正案**: この問題を解決するために、どのファイルのどの部分をどう書き換えればよいか（具体的なソースコードの差分や設定変更案）。
