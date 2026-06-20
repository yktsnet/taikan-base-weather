## SQS DLQ（デッドレターキュー）の再投入（Redrive）機能の実装
id: 21
skill: pr-workflow
branch-slug: sqs-dlq-redrive
github_issue:
status: close
type: feat
対象: src/app/Http/Controllers/Admin/VerificationController.php (変更), src/resources/js/Pages/Admin/Verification.jsx (変更), src/app/Services/SqsQueueService.php (変更)
内容: 管理者検証パネルから、デッドレターキュー（DLQ）に滞留したメッセージを元の処理用メインキューに再投入（Redrive）するためのバックエンドAPIおよびフロントエンドUI（再投入ボタン）を実装します。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - DLQに滞留メッセージが存在するとき、検証パネルの「デッドレターキュー（DLQ）」カード内に「再投入（Redrive）を開始する」ボタンが表示されること。
  - 再投入ボタンをクリックした際、バックエンドAPIを通じてメッセージがメインキューへ再投入され、DLQ滞留件数が減少すること（ポーリング等で検証パネル上のメトリクスに即時反映されること）。
  - SqsQueueService等でSQS API（ReceiveMessage、SendMessage、DeleteMessage）を用いた再投入バッチ/ループ処理がエラーなく実行できること。

---

## 実装仕様

### 1. SQS キューサービスへの再投入処理の追加
- **`src/app/Services/SqsQueueService.php`** [MODIFY]:
  - SQSからDLQのメッセージを受信（`ReceiveMessage`）し、メインキューへ送信（`SendMessage`）後、DLQから削除（`DeleteMessage`）する、または一括で再投入を行うメソッドを追加します。
  - メッセージが空になるまでループ、あるいは1回あたり最大件数（例: 10件単位など）を指定して順次処理する仕組みを設けます。

### 2. バックエンドAPIコントローラーの実装
- **`src/app/Http/Controllers/Admin/VerificationController.php`** [MODIFY]:
  - SQS DLQからの再投入をトリガーするAPIエンドポイント `POST /admin/api/dlq-redrive` を追加します。
  - 実行後、再投入された件数をレスポンスとして返却します。

### 3. フロントエンド検証パネルのUI変更
- **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - DLQの監視カード内に「再投入を開始する」ボタンを追加します。
  - ボタンは `metrics.dlq.pending > 0` の場合のみ活性化（または表示）し、送信中はローディング状態にします。
  - 実行成功時にメッセージを表示し、メトリクス情報を即時更新（`fetchMetrics()` の呼び出し）します。
