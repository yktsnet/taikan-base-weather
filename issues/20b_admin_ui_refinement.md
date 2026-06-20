## 管理者UIの微調整と一貫性の徹底
id: 20b
skill: pr-workflow
branch-slug: admin-ui-refinement
github_issue:
status: close
type: fix
対象: src/resources/js/Pages/Dashboard.jsx (変更), src/resources/js/Pages/Admin/Verification.jsx (変更)
内容: ダッシュボード右上の「Verification Mode」ボタンをニュートラルなグレーアウトラインに変更して青色の重複を完全に解消します。また、検証画面（Verification.jsx）のカード幅がヘッダーの右端と完全に揃うよう、`w-full` の明示などレイアウトの微調整を行います。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - ダッシュボード右上の「Verification Mode」がグレー系のアウトラインボタンになり、青紫の「Alert History」と明確に区別できること。
  - 検証画面（Verification.jsx）のカード右端と、ヘッダー部のアクションボタンの右端が垂直にぴったり揃うこと。

---

## 実装仕様

### 1. ダッシュボード右上のボタン配色をグレーアウトラインへ変更
- **`src/resources/js/Pages/Dashboard.jsx`** [MODIFY]:
  - `Verification Mode` ボタンのスタイルを `border-indigo-600 text-indigo-600` から、ニュートラルなダークグレー系アウトライン（`border-slate-600 text-slate-700 bg-white hover:bg-slate-50`）に変更します。
  - これにより、青紫色の「Alert History」ボタンと明確に視覚的な役割（プライマリ vs セカンダリ）が区別され、「どちらも青で分かりにくい」問題が完全に解消されます。

### 2. 検証画面のコンテナ・カード幅の同期修正
- **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - グリッドレイアウト要素および各カード要素の横幅が自動収縮するのを防ぎ、コンテナ幅いっぱいに広がるよう `w-full` を明示的に指定します。
  - グリッドコンテナ:
    `className="grid grid-cols-1 lg:grid-cols-3 gap-8 w-full"`
  - 各カード（Column 1, 2, 3 のラッパー div）:
    `className="lg:col-span-1 bg-white shadow rounded-lg border border-gray-200 p-6 flex flex-col justify-between w-full"`
  - これにより、ウィンドウ幅が狭い場合（1カラム縦並び）や広い場合に関わらず、カードの右端とヘッダー右上の「ログアウト」ボタンの右端が垂直にぴったり揃うようにします。
