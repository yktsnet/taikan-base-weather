## 技術スタックの最新化（Laravel 13、Tailwind v4、Vite 8、React 19への移行）
id: 07
skill: pr-workflow
branch-slug: upgrade-stack
github_issue:
status: open
type: feat
対象: src/composer.json (変更), src/package.json (変更), src/vite.config.js (変更), src/resources/css/app.css (変更), src/tailwind.config.js (削除), src/postcss.config.js (削除), issues/done/07_upgrade-stack_pr.md (新規)
内容: プロジェクト全体の技術スタックを最新化（PHP 8.4、Laravel 13、React 19、Tailwind v4、Vite 8、Inertia 2.0）し、Tailwind v4 の CSS-first 構成に設定を移行する。
確認: 各種設定ファイルに構文エラーがないことの目視確認。
---
## 実装仕様

### 1. PHP/Laravel 依存関係のアップグレード
- **`src/composer.json`**:
  - `php` バージョンを `^8.3` または `^8.4` に変更する。
  - `laravel/framework` を `^13.0` にアップグレードする。
  - `laravel/tinker` を `^3.0` にアップグレードする。
  - `phpunit/phpunit` を `^12.0`（またはLaravel 13互換の最新版）にアップグレードする。
  - `nunomaduro/collision` などのその他 dev-dependencies も Laravel 13 互換のバージョンに更新する。

### 2. フロントエンド依存関係のアップグレード
- **`src/package.json`**:
  - `react` および `react-dom` を `^19.0.0` にアップグレードする。
  - `tailwindcss` を `^4.0.0` にアップグレードする。
  - `vite` を `^8.0.0` にアップグレードする。
  - `@inertiajs/react` を `^2.0.0` にアップグレードする。
  - `@tailwindcss/vite`（Tailwind v4用のViteプラグイン）を新規依存関係として追加する。
  - `@vitejs/plugin-react` を `^5.0.0` にアップグレードする。
  - 不要となった `autoprefixer` および `postcss` パッケージを依存関係から削除する。

### 3. 設定ファイルの移行とクリーンアップ
- **`src/tailwind.config.js`** [DELETE]:
  - Tailwind v4 は CSS-first 設定になるため、このファイルを削除する。
- **`src/postcss.config.js`** [DELETE]:
  - ポストプロセッサを通さないビルド統合にするため、このファイルを削除する。
- **`src/vite.config.js`**:
  - ポストプロセッサの代わりに、新規に導入された `@tailwindcss/vite` プラグインをインポートして `plugins` 配列に追加する。
- **`src/resources/css/app.css`**:
  - 古い `@tailwind base; @tailwind components; @tailwind utilities;` の記述を削除し、最新の `@import "tailwindcss";` に書き換える。

---

## issues/done/07_upgrade-stack_pr.md の出力内容（必須）
Julesは、以下のテキストをそのままPR控えファイル `issues/done/07_upgrade-stack_pr.md` に含めて作成すること。

```markdown
## 変更内容
- `composer.json` において、PHPのバージョンを 8.4、Laravel Frameworkのバージョンを 13.0 にアップグレードし、それに伴う開発依存ライブラリ（PHPUnit等）も最新化しました。
- `package.json` において、React 19、Tailwind v4、Vite 8、Inertia React 2.0 にアップグレードしました。
- `tailwind.config.js` および `postcss.config.js` を削除し、Vite設定に `@tailwindcss/vite` プラグインを導入して Tailwind v4 の CSS-first 構成へ移行しました。
- `app.css` 内の古い `@tailwind` ディレクティブを `@import "tailwindcss";` へ書き換えました。

## 静的確認結果
- 各種設定ファイルの定義にシンタックスエラーがないことを目視確認しました。

## 検証手順
user様は、ローカル環境（PHP 8.3/8.4が利用可能であること）で以下の手順により動作確認をお願いします。

1. `src` ディレクトリに移動し、既存のビルド成果物とキャッシュを削除してからパッケージを再インストールします。
   ```bash
   cd src
   rm -rf node_modules package-lock.json
   npm install
   npm run build
   ```
2. PHPの依存関係を最新の定義に基づいて更新します。
   ```bash
   composer update
   ```
3. ローカルサーバーを起動し、アセットが正常にコンパイルされダッシュボード画面が表示されることを確認します。
   ```bash
   php artisan serve
   ```
```
