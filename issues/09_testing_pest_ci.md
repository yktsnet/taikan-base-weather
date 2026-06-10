## Pestテストスイートの導入とGitHub ActionsによるCI構築
id: 09
skill: pr-workflow
branch-slug: testing-pest-ci
github_issue:
status: open
type: feat
対象: src/composer.json (変更), src/tests/Feature/PollerTest.php (新規), src/tests/Feature/QueueWorkerTest.php (新規), src/tests/Unit/StationTest.php (新規), .github/workflows/test.yml (新規), issues/done/09_testing-pest-ci_pr.md (新規)
内容: Pest (または PHPUnit) テストスイートをプロジェクトに導入し、データ収集ポーリングやキューワーカーによるアラート判定機能の自動テストを実装する。また、GitHub ActionsによるCI（自動テスト、スタイルチェック）を構築する。
確認: テストコードを実行し、すべてパスすること。また GitHub Actions ワークフローが正しく定義されていること。
---
## 実装仕様

### 1. テストスイートのセットアップ
- **`src/composer.json`** [MODIFY]:
  - `pestphp/pest` や `pestphp/pest-plugin-laravel` 等を開発依存関係に追加する（もしくは標準の PHPUnit 設定を強化する）。
  - テスト用の環境変数定義を `phpunit.xml` に設定する（データベースとしてインメモリの SQLite を使用する設定 `DB_CONNECTION=sqlite` `DB_DATABASE=:memory:`）。

### 2. テストの作成
- **`src/tests/Unit/StationTest.php`** [NEW]:
  - 観測所のしきい値判定（caution/warning/danger）ロジックが期待通り判定を行うかテストする。
- **`src/tests/Feature/PollerTest.php`** [NEW]:
  - APIポーリングコマンドが正常に動作し、`Http::fake()` でモックされた外部データが正しく取得されて SQS（またはデータベース）へ渡されるかテストする。
- **`src/tests/Feature/QueueWorkerTest.php`** [NEW]:
  - キューからデータを受信した Job が正常に走り、データベースへの保存とアラートメール送信（`Mail::fake()`）がトリガーされるかテストする。

### 3. CI ワークフローの構築
- **`.github/workflows/test.yml`** [NEW]:
  - GitHub Actions のワークフローを作成し、コードスタイル確認（Pint 等）、静的解析（PHPStan 等）、および Pest テストスイートの実行が PR/Push 時に自動実行されるようにする。

---

## issues/done/09_testing-pest-ci_pr.md の出力内容（必須）
Julesは、以下のテキストをそのままPR控えファイル `issues/done/09_testing-pest-ci_pr.md` に含めて作成すること。

```markdown
## 変更内容
- `composer.json` に Pest (または PHPUnit の関連パッケージ) を追加し、テスト環境としてインメモリの SQLite を使用するように `phpunit.xml` を設定しました。
- 観測データのしきい値判定ロジックに対するユニットテスト `StationTest.php` を作成しました。
- APIポーリングコマンドに対する機能テスト `PollerTest.php`、キューワーカーによるイベント処理とアラートメール送信に対する機能テスト `QueueWorkerTest.php` を作成しました。
- プルリクエストおよびプッシュ時に、スタイルチェック、Linter、テストスイートを自動実行する `.github/workflows/test.yml` を構築しました。

## 静的確認結果
- テストスイートがローカル環境ですべてグリーン（パス）であることを確認しました。
- GitHub Actions のワークフロー定義に構文エラーがないことを確認しました。

## 検証手順
user様は、以下の手順でテストの実行をご確認ください。

1. `src` ディレクトリ配下でテストを実行し、すべてのテストがパスすることを確認します。
   ```bash
   cd src
   composer install
   php artisan test
   ```
2. GitHub にプッシュした際、リポジトリの Actions タブにて CI ワークフローが正常にパスすることを確認します。
```
