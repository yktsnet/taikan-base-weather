## ダッシュボード基本画面の実装
id: 06
skill: pr-workflow
branch-slug: dashboard-ui
github_issue:
status: open
type: feat
対象: src/app/Http/Controllers/DashboardController.php (新規), src/routes/web.php (変更), src/resources/js/Pages/Dashboard.jsx (新規), src/resources/js/Pages/StationDetail.jsx (新規), src/resources/js/Pages/AlertHistory.jsx (新規), issues/done/06_dashboard-ui_pr.md (新規)
内容: Inertia.js と React を用いて、観測所一覧（ステータス表示付き）、詳細（直近履歴表示）、およびアラート履歴を一覧表示するダッシュボード基本画面の実装。
確認: php -l による構文チェック、作成されたファイルパスの整合性の目視確認。
---
## 実装仕様

### 1. ダッシュボードコントローラ
- **`src/app/Http/Controllers/DashboardController.php`**:
  - `index()`:
    - すべての観測所（`stations`）を取得し、各観測所に関連する最新1件の水位データ（`water_levels`）と最新1件の気象データ（`weather_records`）をEager Load（またはサブクエリ等で効率的に取得）して紐づける。
    - Inertia経由で `Dashboard` ページコンポーネントを呼び出す。
  - `show($id)`:
    - 指定されたIDの観測所を取得。
    - 直近24件（あるいは直近24時間分）の水位および気象履歴データを取得する。
    - Inertia経由で `StationDetail` ページコンポーネントを呼び出す。
  - `alerts()`:
    - すべてのアラート履歴（`alerts`）を、関連する観測所名（`stations`）をロードした状態で取得する（発生時刻の降順）。
    - Inertia経由で `AlertHistory` ページコンポーネントを呼び出す。

### 2. ルーティングの定義
- **`src/routes/web.php`**:
  - ダッシュボードの各ページエンドポイントを設定する。
    - `GET /` または `GET /dashboard` -> `DashboardController@index`
    - `GET /stations/{id}` -> `DashboardController@show`
    - `GET /alerts` -> `DashboardController@alerts`

### 3. React ページコンポーネント
- **`src/resources/js/Pages/Dashboard.jsx`**:
  - 観測所の一覧をTailwind CSSを用いてカードまたはグリッド形式で美しく表示する。
  - 警戒ステータス（`alert_status`）に応じてバッジの色を動的に変更する（`normal` -> 緑, `caution` -> 黄, `warning` -> 橙, `danger` -> 赤）。
  - 各観測所の最新水位、気温、降水量を表示し、詳細画面へのリンクボタンを配置する。
- **`src/resources/js/Pages/StationDetail.jsx`**:
  - 観測所の基本情報に加え、直近の水位データと気象データをテーブル形式で時系列に表示する。
  - 戻るボタンを配置し、一覧画面へ遷移できるようにする。
- **`src/resources/js/Pages/AlertHistory.jsx`**:
  - これまでに発生したアラート履歴を表形式で一覧表示する（発生時刻、観測所名、トリガー時の水位、警戒レベル）。

---

## issues/done/06_dashboard-ui_pr.md の出力内容（必須）
Julesは、以下のテキストをそのままPR控えファイル `issues/done/06_dashboard-ui_pr.md` に含めて作成すること。

```markdown
## 変更内容
- ダッシュボード用のコントローラ（DashboardController）を作成し、観測所一覧、詳細、アラート履歴のデータを供給するAPI/Inertiaルートを実装しました。
- web.php にダッシュボード関連のルートを追加しました。
- Inertia.js+React を用いて、観測所一覧画面（Dashboard.jsx）、詳細画面（StationDetail.jsx）、アラート履歴画面（AlertHistory.jsx）をTailwind CSSによるリッチなデザインで実装しました。

## 静的確認結果
- php -l による構文チェックをすべての新規作成・編集PHPファイルに対して実行し、エラーがないことを確認。
  - `src/app/Http/Controllers/DashboardController.php` : No syntax errors detected

## 検証手順
user様は、ローカル環境で以下の手順により動作確認をお願いします。

1. `src` ディレクトリに移動し、Viteアセットと依存関係のビルドを確認します。
   ```bash
   cd src
   npm install
   npm run build
   ```
2. ローカルサーバーを起動し、ブラウザで `/` または `/dashboard` にアクセスして観測所一覧が表示されることを確認します。
   ```bash
   php artisan serve
   ```
3. 各観測所の「詳細」リンク、および上部ナビゲーションから「アラート履歴（`/alerts`）」画面へ遷移し、正常にデータが表示されることを確認します。
```
