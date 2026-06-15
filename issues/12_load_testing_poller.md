## 負荷テスト用Pollerコマンドの実装とSQSバッチ送信サポート
id: 12
skill: pr-workflow
branch-slug: load-testing-poller
github_issue:
status: open
type: feat
対象: src/app/Services/SqsQueueService.php (変更), src/app/Console/Commands/LoadTestPoller.php (新規)
内容: 高トラフィックデータ受信の負荷検証のため、数千〜数万件規模の擬似センサーイベントをSQSへ高速で投入するArtisanコマンドを実装する。また、SQSへの送信を高速化するため、SQSのバッチ送信APIをサポートする。
確認: 構文エラーがないこと、およびローカルのLocalStack SQSキューに対してメッセージが正常かつ高速に投入されること。

---

## 実装仕様

### 1. SQSバッチ送信メソッドの追加 (`SqsQueueService.php`)
- `SqsQueueService` に `sendMessageBatch(string $queueUrl, array $messages): bool` メソッドを実装する。
  - SQSの `sendMessageBatch` APIを利用し、送信データを最大10件ずつのグループ（SQSの制限）に分割して一括送信する。
  - `Aws\Sqs\SqsClient` の `sendMessageBatch` を使用してリクエストを行う。
  - 各メッセージには、バッチ内でユニークな `Id` が必要であるため、連番などの文字列を生成して設定する。

### 2. 負荷テスト用Pollerコマンドの追加 (`LoadTestPoller.php`)
- Artisanコマンド `app:load-test-poller` を新規作成する。
- 以下のシグネチャを持つ：
  `app:load-test-poller {--count=1000 : 送信するメッセージの総数} {--type=all : 送信するデータの種類(water|weather|all)}`
- 生成ロジック：
  - データベースに登録されている観測所コード（`Station::pluck('code')`）をランダムに割り当てる。
  - 観測時刻は現在時刻とする。
  - 数値（水位や降雨量等）はランダムかつ妥当な範囲で生成する。
- 送信処理：
  - 指定された件数分のダミーデータを生成し、`SqsQueueService::sendMessageBatch` を使ってバッチでSQS（水位データ用、または気象データ用のキュー）へ送信する。
  - コマンドラインに、送信数と送信にかかった時間（秒）、および秒間送信数を表示し、パフォーマンス指標を確認できるようにする。
