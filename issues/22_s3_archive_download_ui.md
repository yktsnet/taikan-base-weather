## S3 CSV日次アーカイブのダウンロードUIの実装
id: 22
skill: pr-workflow
branch-slug: s3-archive-download-ui
github_issue:
status: closed
type: feat
対象: src/app/Http/Controllers/Admin/VerificationController.php (変更), src/resources/js/Pages/Admin/Verification.jsx (変更), src/routes/web.php (変更)
内容: S3（LocalStack）に保存されている日次アーカイブのCSVファイルを、管理者がブラウザから一覧確認し、直接ダウンロードできる仕組みを実装します。ネットワーク環境に左右されず確実にダウンロードできるよう、LaravelバックエンドがS3からファイルを仲介（プロキシ）してレスポンスするストリームダウンロードAPIを採用します。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - 管理者検証パネルに「S3日次アーカイブ」セクションが追加され、S3内のCSVファイル一覧が表示されること。
  - 各CSVファイル行の「ダウンロード」ボタンをクリックした際、対応するCSVファイルがブラウザから正常にダウンロードできること。
  - アーカイブが存在しない場合、「アーカイブファイルが見つかりません。」等のテキストが表示されること。

---

## 実装仕様

### 1. バックエンドAPIの実装 (VerificationController.php)
- **`src/app/Http/Controllers/Admin/VerificationController.php`** [MODIFY]:
  - **S3ファイル一覧取得API**: `getS3Archives(Request $request): JsonResponse`
    - `Storage::disk('s3')->allFiles('water-levels')` でファイル一覧を取得します。
    - ファイルサイズや最終更新日時をフォーマットして返却します。
  - **S3ファイルダウンロードAPI**: `downloadS3Archive(Request $request): Symfony\Component\HttpFoundation\StreamedResponse`
    - クエリパラメータ `path` を受け取り、`water-levels/` から始まる有効なパスであるかをバリデーションします（セキュリティ対策）。
    - ファイルが存在しない場合は404を返却します。
    - `Storage::disk('s3')->download($path)` を実行してストリームを直接返却します。

### 2. ルート定義の追加 (web.php)
- **`src/routes/web.php`** [MODIFY]:
  - `admin` プレフィックスかつ `auth` ミドルウェアのグループ内に、以下の2つのルートを定義します。
    - `Route::get('/api/s3-archives', [VerificationController::class, 'getS3Archives'])->name('admin.api.s3_archives');`
    - `Route::get('/api/s3-archives/download', [VerificationController::class, 'downloadS3Archive'])->name('admin.api.s3_archive_download');`

### 3. フロントエンドのUI実装 (Verification.jsx)
- **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - メトリクス表示グリッドの下（または並列）に、4つ目のセクションとして「📦 S3日次アーカイブ」カードを追加します。
  - ページ読み込み時にファイル一覧をAPIから取得し、テーブル形式でファイル名、サイズ、最終更新日時、ダウンロードボタンを表示します。
  - ダウンロード処理は、`window.open('/admin/api/s3-archives/download?path=' + encodeURIComponent(path))` または `location.href` を用いて、ブラウザのネイティブなダウンロードダイアログを起動します。
