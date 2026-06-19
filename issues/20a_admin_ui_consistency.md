## 管理者UIとダッシュボードのデザイン一貫性の修正
id: 20a
skill: pr-workflow
branch-slug: admin-ui-consistency
github_issue:
status: close
type: fix
対象: src/resources/js/Pages/Dashboard.jsx (変更), src/resources/js/Pages/Admin/Verification.jsx (変更)
内容: 管理者ログイン時のボタン配色の差別化（どちらも青で分かりにくい問題の解消）、および検証画面のコンテナ幅やレイアウト構造を一般ダッシュボードのスタイルと完璧に統一する修正を行います。
確認: 
  - 正常にアセットビルド（Viteビルド）が通ること。
  - 一般ダッシュボードの「Alert History」と「Verification Mode」のボタン配色が差別化されていること。
  - 検証画面（Verification.jsx）のヘッダーバー（画面幅いっぱいの白ヘッダー）が廃止され、ダッシュボードと同様の「グレー背景・中央寄せコンテナ」のレイアウト枠で完全に統一されていること。
  - 【超重要】PR作成前に、必ずPR本文と同じ内容を `issues/done/20a_admin-ui-consistency_pr.md` に新規ファイルとして書き出し、実装コードと同一 of the コミットに含めること。

---

## 実装仕様

### 1. 一般ダッシュボードのボタン配色の差別化
- **`src/resources/js/Pages/Dashboard.jsx`** [MODIFY]:
  - `Alert History` ボタンはプライマリカラー（`bg-indigo-600 text-white`）のままとします。
  - `Verification Mode` ボタンは、同じ青系で並んで分かりにくいため、アウトラインスタイル（白背景、インディゴ枠、インディゴ文字: `bg-white border border-indigo-600 text-indigo-600 hover:bg-indigo-50`）に変更して明確に差別化します。

### 2. 検証画面（Verification.jsx）のレイアウト構造と余白の統一
- **`src/resources/js/Pages/Admin/Verification.jsx`** [MODIFY]:
  - 画面最上部に配置していた「画面横幅いっぱいに広がる白ヘッダー」を廃止します。
  - 一般ダッシュボードと同様に、グレー背景（`bg-gray-100`）の中に `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` の共通コンテナを配置し、その中に直接「タイトル」と「アクションボタン（一般ダッシュボードに戻る、ログアウト）」を配置するフラットな構造に変更します。
  - これにより、一般ダッシュボードと検証画面のメニュー幅や左右の余白が完璧に一致するようになります。
  - グリッドのブレークポイント（カラム数）を、画面幅に合わせて崩れにくいよう再調整し、デザインの調和を図ります。
