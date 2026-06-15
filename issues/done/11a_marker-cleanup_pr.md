## 変更内容
地図上のピン（マーカー）の表示を、不要なPNG画像からCSS/SVGを用いたカスタムマーカーに変更しました。
具体的には：
- `src/resources/js/Pages/Dashboard.jsx` において、Leaflet標準のPNG画像によるアイコン指定（`L.Icon`）を廃止し、`L.divIcon` を用いたインラインSVG形式のカスタムマーカーに変更しました。
- `Lucide` のピンアイコンを再現したSVGを使用し、`normal`, `caution`, `warning`, `danger` の各状況に応じて色を動的に変更できるようクラス名をバインドしました。
- 注意が必要な状態（`normal` 以外）のマーカーに対しては、視覚的に目立たせるためのパルス（波紋）アニメーションをCSSで実装しました。
- `src/resources/css/app.css` にカスタムマーカーのスタイルおよびパルスアニメーションのCSSキーフレーム定義を追加しました。
- `src/public/images/` に存在していた不要なPNGマーカー画像およびシャドウ画像をリポジトリから完全に削除しました。

## 静的確認結果
- `npm run build` を実行し、エラーなくビルドが通ることを確認しました。
- 変更ファイル一覧:
  - 変更: `src/resources/css/app.css`
  - 変更: `src/resources/js/Pages/Dashboard.jsx`
  - 削除: `src/public/images/marker-icon-2x-blue.png`
  - 削除: `src/public/images/marker-icon-2x-green.png`
  - 削除: `src/public/images/marker-icon-2x-orange.png`
  - 削除: `src/public/images/marker-icon-2x-red.png`
  - 削除: `src/public/images/marker-icon-2x-yellow.png`
  - 削除: `src/public/images/marker-icon-2x.png`
  - 削除: `src/public/images/marker-icon.png`
  - 削除: `src/public/images/marker-shadow.png`

## 検証手順
1. フロントエンドのビルドが正常に通ることを確認します：
   ```bash
   npm run build
   ```
2. ブラウザ等でダッシュボードを表示し、地図上のピン（マーカー）がPNG画像から美しいSVGピンアイコンに変わっていること、および観測所の警戒状態（normal: 緑、caution: 黄、warning: 橙、danger: 赤）に応じた色になっていること、さらに `normal` 以外の観測所にパルスアニメーションが適用されていることを確認します。
3. リポジトリ上の `src/public/images/` からPNGマーカー画像が削除されていることを確認します。
