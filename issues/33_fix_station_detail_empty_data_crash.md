## 地点詳細ページが真っ白になる（Chart.js コントローラー未登録）

id: 33
skill: pr-workflow
branch-slug: fix-station-detail-chart-crash
status: done
type: fix
対象: src/resources/js/Pages/StationDetail.jsx
内容: Chart.js の LineController / BarController が未登録のため、混合チャートの描画時に "line" is not a registered controller エラーでページ全体がクラッシュする問題の修正
確認: JSX 構文は目視確認

---

## 症状

- ダッシュボードのカードから「View Details」をクリックすると真っ白なページが表示される
- ダッシュボード自体は正常にデータが表示されている
- F12 Console: `Uncaught Error: "line" is not a registered controller.`

## 根本原因

`StationDetail.jsx` L1-25 で Chart.js のモジュールを手動登録しているが、`LineController` と `BarController` が漏れている:

```js
// 現状の登録（L16-25）
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,    // ← 描画要素（線の見た目）
  BarElement,     // ← 描画要素（棒の見た目）
  Title,
  Tooltip,
  Legend
);
```

このページは混合チャート（`<Chart type='bar'>` に `type: 'line'` のデータセットを含む）を使っているため、描画要素だけでなくチャートタイプの**コントローラー**も登録が必須:
- `LineController` — `type: 'line'` データセットの制御
- `BarController` — `type: 'bar'` データセットの制御

## 修正

import に `LineController`, `BarController` を追加し、`ChartJS.register()` に含める:

```js
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,   // 追加
  BarController,    // 追加
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,   // 追加
  BarController,    // 追加
  Title,
  Tooltip,
  Legend
);
```

## 確認

- `/stations/{id}` でチャートが描画されること
- 水位（折れ線）と降水量（棒）の混合チャートが正しく表示されること
- 警戒・危険水位の破線が表示されること
- 目視確認
