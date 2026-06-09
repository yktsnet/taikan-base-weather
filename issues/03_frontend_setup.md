## フロントエンド環境の構築および観測所Seederの実装
id: 03
skill: pr-workflow
branch-slug: frontend-setup
github_issue:
status: open
type: feat
対象: src/vite.config.js (新規), src/tailwind.config.js (新規), src/postcss.config.js (新規), src/resources/views/app.blade.php (新規), src/resources/js/app.jsx (新規), src/database/seeders/StationSeeder.php (新規), src/database/seeders/DatabaseSeeder.php
内容: Inertia + React + Vite + Tailwind CSSを動作させるためのフロントエンド基本ファイルの配置と、関西圏10観測所のデータを投入するSeederの作成。
確認: php -l による構文チェック、作成されたファイルパスの整合性の目視確認。
---
## 実装仕様

### 1. フロントエンド構成ファイルの配置
- **`src/vite.config.js`**:
  - Laravel Vite PluginとReactプラグインを読み込み、`src/resources/js/app.jsx` をエントリーポイントとして設定する。
- **`src/tailwind.config.js`**:
  - `src/resources/**/*.blade.php`, `src/resources/**/*.js`, `src/resources/**/*.jsx` を探索パスに含める。
- **`src/postcss.config.js`**:
  - `tailwindcss` と `autoprefixer` をプラグインに指定する。
- **`src/resources/views/app.blade.php`**:
  - InertiaのHTML構造を定義する。`@viteReactRefresh`, `@vite(['resources/js/app.jsx'])`, `@inertiaHead` を含む。
- **`src/resources/js/app.jsx`**:
  - Inertiaのフロントエンド側初期化コード。`createInertiaApp` を用いてReactアプリをマウントする。

### 2. データベースシードデータの設定
- **`src/database/seeders/StationSeeder.php`**:
  - 関西圏の10観測所（淀川、大和川など）のマスタデータを `stations` テーブルに挿入する。
  - 各観測所には、`code`, `name`, `river_name`, `prefecture`, `lat`, `lng`, `warning_level`, `danger_level` を適切に設定する。
- **`src/database/seeders/DatabaseSeeder.php`**:
  - `StationSeeder` を呼び出すように変更する。
