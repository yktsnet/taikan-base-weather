## 変更内容
- `composer.json` に Pest (または PHPUnit の関連パッケージ) を追加し、テスト環境としてインメモリの SQLite を使用するように `phpunit.xml` を設定しました。
- 観測データのしきい値判定ロジックに対する機能テスト `StationTest.php` を作成しました。
- APIポーリングコマンドに対する機能テスト `PollerTest.php`、キューワーカーによるイベント処理とアラートメール送信に対する機能テスト `QueueWorkerTest.php` を作成しました (`Http::fake()`、`Mail::fake()` も利用しています)。
- プルリクエストおよびプッシュ時に、スタイルチェック、Linter、テストスイート、静的解析を自動実行する `.github/workflows/test.yml` を構築しました。
- Dockerコンテナ内でVite開発サーバーを実行した際にホストブラウザからアクセス可能にするため、`vite.config.js` に `server` 設定（`host: '0.0.0.0'` 等）を反映しました。

## 静的確認結果
- テストスイートがローカル環境ですべてグリーン（パス）であることを確認しました。
- GitHub Actions のワークフロー定義および `vite.config.js` の構文にエラーがないことを確認しました。

## 検証手順
user様は、以下の手順でテストの実行およびViteの設定をご確認ください。

1. `src` ディレクトリ配下でテストを実行し、すべてのテストがパスすることを確認します。
   ```bash
   cd src
   composer install
   php artisan test
   ```
2. GitHub にプッシュした際、リポジトリの Actions タブにて CI ワークフローが正常にパスすることを確認します。
