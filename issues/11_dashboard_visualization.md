## ダッシュボードの可視化強化（グラフと地図の導入）
id: 11
skill: pr-workflow
branch-slug: dashboard-visualization
github_issue:
status: open
type: feat
対象: src/package.json (変更), src/resources/js/Pages/Dashboard.jsx (変更), src/resources/js/Pages/StationDetail.jsx (変更)
内容: 詳細画面に基準水位ラインを重ね合わせた水位・降水量の時系列推移グラフを、一覧画面に警戒状況に応じてピンの色が変化する観測所マップ（地図UI）を実装する。
確認: npm run build がエラーなく通ることを確認。PR作成前に、PR本文と同じ内容を `issues/done/11_dashboard-visualization_pr.md` に書き出し、実装コードと同一コミットに含めること。

---

## 実装仕様

### 1. フロントエンドライブラリの導入
- 時系列グラフ表示用に `chart.js` および `react-chartjs-2` を導入する。
- 地図表示用に `leaflet` および `react-leaflet` を導入する。
- `src/package.json` にこれらのパッケージを追加し、アセットビルドが正常に通るように構成する。

### 2. 観測所詳細画面でのグラフ表示 (`StationDetail.jsx`)
- 直近の水位データと気象データ（降雨量など）を1つのグラフ（または並列グラフ）で分かりやすく可視化する。
- 観測所マスタに定義されている「注意水位（warning_level）」と「警戒水位（danger_level）」の水平ライン（ボーダーライン）をグラフ上に重ねて表示し、危険度が視覚的に一目で判別できるようにする。

### 3. ダッシュボード一覧画面での地図表示 (`Dashboard.jsx`)
- 関西圏の10観測所の位置情報（緯度・経度）を元に、Leaflet地図上にピン（マーカー）をプロットする。
- 各観測所の最新の水位状態（normal/caution/warning/danger）に応じて、ピンの色を動的に変更する（例：正常＝緑、注意＝黄、警戒・危険＝赤など）。
- マーカーをクリックした際に、観測所名、河川名、最新水位が表示されるポップアップを実装し、詳細画面へのリンクを設ける。
