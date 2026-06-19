## ログイン画面のテーマとダッシュボードとの一貫性向上（ライトテーマ化）
id: 18a
skill: pr-workflow
branch-slug: login-theme-fix
github_issue:
status: close
type: fix
対象: src/resources/js/Pages/Auth/Login.jsx (変更)
内容: ログイン画面が一般ダッシュボード（bg-gray-100 や白ベースのライトテーマ）と乖離し、浮いて見えてしまっているため、ダッシュボードのトーン＆マナーに合わせたライトテーマのクリーンなデザインへ修正します。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - ログイン画面の背景がライトグレー（bg-gray-100など）になり、白カードとインディゴ系のボタンで統一されていること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/18a_login-theme-fix_pr.md` に新規ファイルとして書き出し、実装コードと同一のコミットに含めること。この控えファイルの作成を絶対に失念しないこと。

---

## 実装仕様

### 1. ログイン画面（Login.jsx）のスタイリング修正
- **`src/resources/js/Pages/Auth/Login.jsx`** [MODIFY]:
  - 全体背景を `bg-slate-900` からダッシュボードと同じ `bg-gray-100` に変更します。
  - フォームカードを `bg-slate-800` (ダーク) から `bg-white shadow rounded-lg border border-gray-200` (ライト) に変更します。
  - テキストカラーをライトテーマ用（`text-gray-900` や `text-gray-600`）に修正します。
  - 各入力欄（Input）を `bg-slate-700` から `bg-white border-gray-300 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500` に変更します。
  - ログインボタンの背景色を、ダッシュボードのボタン（Alert History）と親和性の高いインディゴブルー（`bg-indigo-600 hover:bg-indigo-700`）に変更します。
  - デモアカウントの注記エリアも、ライトグレーや薄いブルーベース（`bg-blue-50 border-blue-200 text-blue-800`）に調和させます。
