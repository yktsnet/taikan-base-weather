## 変更内容
S3（LocalStack）に保存されている日次アーカイブのCSVファイルを、管理者がブラウザから一覧確認し、直接ダウンロードできる仕組みを実装しました。
ネットワーク環境に左右されず確実にダウンロードできるよう、LaravelバックエンドがS3からファイルを仲介（プロキシ）してレスポンスするストリームダウンロードAPIを採用しています。

### 具体的な変更内容
1. **`src/app/Http/Controllers/Admin/VerificationController.php`**
   - S3ファイル一覧取得API `getS3Archives` を実装し、`Storage::disk('s3')->allFiles('water-levels')` からファイル一覧を取得し、ファイルサイズや最終更新日時をフォーマットして返却するようにしました。また、更新日時の降順でソートして返却します。
   - S3ファイルダウンロードAPI `downloadS3Archive` を実装しました。クエリパラメータ `path` が `water-levels/` から始まる有効なパスであるかをバリデーション（正規表現 `/^water-levels\/[0-9a-zA-Z_\/\.-]+\.csv$/`）し、存在しない場合は404を返却、存在する場合は `Storage::disk('s3')->download($path)` を実行してストリームを直接返却します。
2. **`src/routes/web.php`**
   - `admin` プレフィックスかつ `auth` ミドルウェアのグループ内に、上記APIのルート `/api/s3-archives` および `/api/s3-archives/download` を定義しました。
3. **`src/resources/js/Pages/Admin/Verification.jsx`**
   - 検証パネル最下部に「S3日次アーカイブ一覧」セクションを追加しました。
   - ページ読み込み時にファイル一覧をポーリング（5秒間隔）で取得し、テーブル形式でファイル名、サイズ、最終更新日時、ダウンロードボタンを表示します。
   - ダウンロードボタンクリック時に `window.location.href` を用いてブラウザのネイティブダウンロードダイアログを起動します。

## 静的確認結果
- `find src/app -name "*.php" | xargs -I{} php -l {}` により、全PHPファイルの構文にエラーがないことを確認しました。
- `npm run build` が正常に実行され、Viteのアセットビルド（JS/CSS）がエラーなく完了することを確認しました。

### 変更ファイル一覧
- `issues/22_s3_archive_download_ui.md`
- `src/app/Http/Controllers/Admin/VerificationController.php`
- `src/resources/js/Pages/Admin/Verification.jsx`
- `src/routes/web.php`

## 検証手順
1. ローカル環境（Laravel Sail等）を起動し、管理者アカウントでログインして検証パネル `/admin/verification` にアクセスします。
2. 画面下部に「S3日次アーカイブ一覧」セクションが追加されており、S3内のCSVファイルが表示されることを確認します。
3. アーカイブファイルが存在しない場合は「日次アーカイブCSVファイルが見つかりません。」というテキストが表示されることを確認します。
4. ファイルが存在する場合、各行の「ダウンロード」ボタンをクリックすると、対応するCSVファイルがブラウザのダウンロードダイアログ経由でダウンロードされ、ローカルに保存されることを確認します。
