## 変更内容
Inertia.js + React + Vite + Tailwind CSS を動かすための最小フロントエンド設定ファイルを配置し、動作確認用の観測所（関西圏の10箇所）のマスタデータをSeederとして実装しました。

- **フロントエンド構成ファイルの配置**
  - `src/vite.config.js` (新規) : Vite、Laravel Vite Plugin、Reactプラグインの連携設定
  - `src/tailwind.config.js` (新規) : 探索パス（`resources/**/*.blade.php`, `resources/**/*.jsx`等）の設定
  - `src/postcss.config.js` (新規) : TailwindとAutoprefixerの連携設定
  - `src/resources/views/app.blade.php` (新規) : InertiaのエントリポイントとなるBlade
  - `src/resources/js/app.jsx` (新規) : Inertia Reactの初期化・マウントスクリプト
  - `src/resources/css/app.css` (新規) : Tailwindディレクティブの定義

- **データベースシードデータの設定**
  - `src/database/seeders/StationSeeder.php` (新規) : 関西圏の10観測所（淀川、大和川、武庫川、紀の川水系）のマスタデータ（注意・警戒水位、緯度経度含む）を `stations` テーブルへ登録するSeeder
  - `src/database/seeders/DatabaseSeeder.php` (新規) : `StationSeeder` を呼び出す設定を追加

## 静的確認結果
- 作成されたすべてのPHPファイルに対して `php -l` を実行し、構文エラーがないことを確認しました。
  - `src/database/seeders/StationSeeder.php` : `No syntax errors detected`
  - `src/database/seeders/DatabaseSeeder.php` : `No syntax errors detected`
- 作成されたフロントエンド関連ファイルのパスと記述内容について、目視による整合性の確認を行いました。

### 変更ファイル一覧
- `src/vite.config.js` (新規)
- `src/tailwind.config.js` (新規)
- `src/postcss.config.js` (新規)
- `src/resources/views/app.blade.php` (新規)
- `src/resources/js/app.jsx` (新規)
- `src/resources/css/app.css` (新規)
- `src/database/seeders/StationSeeder.php` (新規)
- `src/database/seeders/DatabaseSeeder.php` (新規)

## 検証手順
user様は、ローカル環境で以下の手順により動作確認をお願いします。

1. `src` ディレクトリに移動し、依存関係を解決します。
   ```bash
   cd src
   npm install
   ```
2. Viteによるアセットビルドを実行し、エラーなく完了することを確認します。
   ```bash
   npm run build
   ```
3. データベースが起動しており、マイグレーションが適用済みの状態で、以下のコマンドを実行してシードデータが投入されることを確認します。
   ```bash
   php artisan db:seed --class=StationSeeder
   ```
   (または `php artisan db:seed` を実行)
