## 実際の水位・気象データ取得への切り替え（モックの廃止）
id: 16
skill: pr-workflow
branch-slug: realtime-api-integration
github_issue:
status: open
type: feat
対象: src/app/Services/RiverApiService.php (新規), src/app/Services/JmaApiService.php (新規), src/app/Console/Commands/WaterLevelPoller.php (変更), src/app/Console/Commands/WeatherPoller.php (変更)
内容: 現在モックデータを生成しているデータ取得コマンドを改修し、国交省（川の防災情報など）および気象庁（アメダスなど）の実データAPIから実際の水位・気象情報を取得してSQSに送信するように切り替える。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/16_realtime-api-integration_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. 国交省水位APIクライアントの実装 (`RiverApiService.php`)
- 国交省（または「川の防災情報」などから取得可能な公開情報）のデータソースから、観測所コード（`station_code`）に一致する最新の水位（m）と観測日時を取得するサービスを実装する。
- APIへのアクセスには Laravel の `Http` ファサード（Guzzleクライアント）を利用し、通信エラー時の例外処理やログ出力を適切に行う。

### 2. 気象庁気象APIクライアントの実装 (`JmaApiService.php`)
- 気象庁（アメダスJSONデータなど）のデータソースから、各観測所に対応する（または最も近い）アメダス地点の最新の気温（℃）および降水量（mm/h）を取得するサービスを実装する。
- APIアクセス部分のエンドポイント設計、エラーハンドリングを同様に行う。

### 3. 水位Pollerのリアルタイム化とAPI結合 (`WaterLevelPoller.php`)
- `app:poll-water-level` コマンド内のモックデータ生成ロジックを廃止する。
- `RiverApiService` をDIして呼び出し、DBに存在する各観測所の実際の最新水位データを取得してSQSキューに送信する形に改修する。

### 4. 気象Pollerのリアルタイム化とAPI結合 (`WeatherPoller.php`)
- `app:poll-weather` コマンド内のモックデータ生成ロジックを廃止する。
- `JmaApiService` をDIして呼び出し、各観測所に対応する実際の最新気象データを取得してSQSキューに送信する形に改修する。
