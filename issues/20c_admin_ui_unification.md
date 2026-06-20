## 一般・管理者UIデザインと言語の完全統一
id: 20c
skill: pr-workflow
branch-slug: admin-ui-unification
github_issue:
status: close
type: fix
対象: src/resources/js/Pages/Dashboard.jsx (変更), src/resources/js/Pages/Admin/Verification.jsx (変更)
内容: 一般ダッシュボードと管理者検証パネルの間で発生している、左上アイコン（シールドアイコンの廃止と波アイコンへの変更）、バッジ（ピル）の表示有無、日本語表記への完全統一、および右上のアウトラインボタン枠線のデザインを一貫したものに修正します。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - 一般ダッシュボードの左上および管理者検証パネルの左上に、共通の波（Waves）アイコンが表示されること。
  - 一般ダッシュボードの左上に「一般ダッシュボード」というバッジ（ピル）が表示されること。
  - 一般ダッシュボードのすべてのテキスト・ボタンが日本語に統一されること（Dashboard -> ダッシュボード表記の整理、Alert History -> アラート履歴、Verification Mode -> 検証モード、Admin Login -> 管理者ログイン）。
  - 右上のアウトラインボタンのスタイルクラスが `border-gray-300 text-gray-700 hover:bg-gray-50 transition` に統一され、不自然な枠線色の差異やフォントの太さの差異が解消されること。

---

## 実装仕様

### 1. 左上のヘッダーデザインおよび共通アイコンの変更
- **`src/resources/js/Pages/Dashboard.jsx`** & **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - どちらの画面も、左上のロゴ表示を「波（Waves）のSVGアイコン ＋ アプリ名『kawa-watch』 ＋ 役割バッジ」の形式に統一します。
  - シールドアイコン（`path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"`）は廃止し、Lucideの `Waves` に類似する以下の波のSVGアイコンを採用します：
    ```html
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-indigo-600">
        <path d="M2 6c.6.5 1.2 1 2.5 1C6 7 7 6 8.5 6c1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
        <path d="M2 12c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
        <path d="M2 18c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
    </svg>
    ```
  - 一般ダッシュボード (`Dashboard.jsx`) の左上：
    - Wavesアイコン ＋ `kawa-watch` ＋ `<span className="ml-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">一般ダッシュボード</span>`
  - 管理者検証パネル (`Verification.jsx`) の左上：
    - Wavesアイコン ＋ `kawa-watch` ＋ `<span className="ml-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800">管理者検証パネル</span>`

### 2. 一般ダッシュボードの表記の日本語化
- **`src/resources/js/Pages/Dashboard.jsx`** [MODIFY]:
  - 右上のボタンテキストを以下のように日本語へ変更します。
    - `Alert History` ➡️ `アラート履歴`
    - `Verification Mode` ➡️ `検証モード`
    - `Admin Login` ➡️ `管理者ログイン`
  - ページタイトル（`<Head title="..." />`）を「ダッシュボード」に変更します。

### 3. 右上アウトラインボタンのスタイル統一
- **`src/resources/js/Pages/Dashboard.jsx`** & **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - 白背景のアウトラインボタンのスタイルクラスを以下に統一します。
    - クラス名：`px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition`
  - 対象ボタン：
    - `Dashboard.jsx`: 「検証モード」および「管理者ログイン」ボタン
    - `Verification.jsx`: 「一般ダッシュボード」ボタン
