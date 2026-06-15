## アーカイブCSVのダッシュボードからのダウンロード機能の実装
id: 18
skill: pr-workflow
branch-slug: s3-download-ui
github_issue:
status: open
type: feat
対象: src/app/Http/Controllers/DashboardController.php (変更), src/resources/js/Pages/Dashboard.jsx (変更), src/routes/web.php (変更)
内容: S3（LocalStack）に保存された日次CSVアーカイブファイル（例: `water_levels_YYYYMMDD.csv`）をダッシュボード画面上に一覧表示し、Web画面から直接ダウンロード（署名付き一時URLまたはストリーム配信）できるUI機能を実装する。
確認: 
  - 構文エラーがないこと、および正常にビルドが通ること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/18_s3-download-ui_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. S3ファイル一覧取得とダウンロード用コントローラーの実装 (`DashboardController.php`, `web.php`)
- `DashboardController@index` メソッドを改修し、S3バケット内の `water-levels/` パスに保存されているCSVファイルの一覧（オブジェクト名、サイズ、更新日時など）を Laravel の `Storage::disk('s3')->listContents()` や `files()` などを利用して取得し、Inertia経由で `Dashboard` ページに渡すようにする。
- CSVファイルをダウンロードするための個別のルート `GET /archive/download` を定義し、コントローラーメソッド（例: `downloadArchive`）を実装する。
  - リクエストパラメータ等でファイルパスを受け取る。
  - Laravelの `Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5))` を利用して一時的な署名付きURLを生成してリダイレクトするか、または `Storage::disk('s3')->download($path)` を使って直接ダウンロードレスポンスを返すようにする。

### 2. ダッシュボード画面へのダウンロードUI追加 (`Dashboard.jsx`)
- [Dashboard.jsx](file:///home/widget/dotfiles/apps/kawa-watch/src/resources/js/Pages/Dashboard.jsx) のUIを改修し、「CSVアーカイブのダウンロード」セクション（またはアコーディオン、サイドバー等）を追加する。
- コントローラーから渡されたアーカイブCSVファイルの一覧をリスト表示し、各アイテムに「ダウンロード」用のリンクやボタンを配置する。
- 各ボタンをクリックすると、上記で作成したダウンロードルートを経由して、安全にローカルPCへCSVファイルがダウンロードされるようにする。
