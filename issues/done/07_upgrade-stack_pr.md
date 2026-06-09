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
