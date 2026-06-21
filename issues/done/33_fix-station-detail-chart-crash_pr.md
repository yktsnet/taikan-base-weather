## 変更内容

Chart.js の `LineController` / `BarController` が未登録のため、混合チャート（`<Chart type='bar'>` に `type: 'line'` のデータセットを含む）の描画時に `"line" is not a registered controller` エラーでページ全体がクラッシュする問題を修正。

`StationDetail.jsx` の Chart.js import と `ChartJS.register()` に `LineController`, `BarController` を追加。

## 静的確認結果

変更ファイル（JSX のみ、PHP 変更なし）:
```
src/resources/js/Pages/StationDetail.jsx
```

JSX 構文は目視確認済み。

## 検証手順

1. `docker compose up -d` でローカル環境を起動
2. ログインしてダッシュボードを表示
3. いずれかの観測所カードの「View Details」をクリック
4. 以下を確認:
   - `/stations/{id}` でチャートが描画されること（真っ白にならないこと）
   - 水位（折れ線）と降水量（棒）の混合チャートが正しく表示されること
   - 警戒水位（オレンジ破線）・危険水位（赤破線）が表示されること
   - F12 Console にエラーが出ていないこと
