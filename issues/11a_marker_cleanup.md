## 地図マーカーのSVG/CSS化とPNGアセットのクリーンアップ
id: 11a
skill: pr-workflow
branch-slug: marker-cleanup
github_issue:
status: close
type: feat
対象: src/resources/js/Pages/Dashboard.jsx (変更), src/public/images/ (削除)
内容: 地図上のピン（マーカー）の表示を、PNG画像からCSS/SVG（Lucide React等）を用いたカスタムマーカー（L.divIcon）に置き換え、不要になったPNG画像ファイルをリポジトリから削除する。
確認: npm run build がエラーなく通ることを確認。PR作成前に、PR本文と同じ内容を `issues/done/11a_marker-cleanup_pr.md` に書き出し、実装コードと同一コミットに含めること。

---

## 実装仕様

### 1. マーカーのCSS/SVG化 (`Dashboard.jsx`)
- 現在 `Dashboard.jsx` で Leaflet の標準アイコン（`L.icon`）としてPNG画像を読み込んでいる部分を廃止する。
- `L.divIcon` を使用し、CSSでスタイリングした円形のマーカー、または `Lucide React` のピンアイコン（SVG）をマーカーとして使用するように変更する。
- 観測所の警戒状況（normal/caution/warning/danger）に応じたピンの色の変化（例：緑/黄/赤など）を、CSSのクラス変更等で動的に制御できるようにする。

### 2. 不要アセットの削除
- `src/public/images/` にダウンロードされていた以下のPNGマーカー画像をリポジトリから完全に削除する。
  - `marker-icon-2x-blue.png`
  - `marker-icon-2x-green.png`
  - `marker-icon-2x-orange.png`
  - `marker-icon-2x-red.png`
  - `marker-icon-2x-yellow.png`
  - `marker-icon-2x.png`
  - `marker-icon.png`
  - `marker-shadow.png`
