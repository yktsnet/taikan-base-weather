## 変更内容
- ダッシュボード用のコントローラ（DashboardController）を作成し、観測所一覧、詳細、アラート履歴のデータを供給するAPI/Inertiaルートを実装しました。
- web.php にダッシュボード関連のルートを追加しました。
- Inertia.js+React を用いて、観測所一覧画面（Dashboard.jsx）、詳細画面（StationDetail.jsx）、アラート履歴画面（AlertHistory.jsx）をTailwind CSSによるリッチなデザインで実装しました。

## 静的確認結果
- php -l による構文チェックをすべての新規作成・編集PHPファイルに対して実行し、エラーがないことを確認。
  - `src/app/Http/Controllers/DashboardController.php` : No syntax errors detected

## 検証手順
user様は、ローカル環境で以下の手順により動作確認をお願いします。

1. `src` ディレクトリに移動し、Viteアセットと依存関係のビルドを確認します。
   ```bash
   cd src
   npm install
   npm run build
   ```
2. ローカルサーバーを起動し、ブラウザで `/` または `/dashboard` にアクセスして観測所一覧が表示されることを確認します。
   ```bash
   php artisan serve
   ```
3. 各観測所の「詳細」リンク、および上部ナビゲーションから「アラート履歴（`/alerts`）」画面へ遷移し、正常にデータが表示されることを確認します。
