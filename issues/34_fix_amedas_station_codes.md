## AMeDAS ステーションコード修正と気象データ実データ化の確認

id: 34
skill: pr-workflow
branch-slug: fix-amedas-station-codes
github_issue:
status: open
type: fix
対象: src/app/Services/JmaApiService.php
内容: AMeDAS ステーションコードのマッピングに誤りがあり、10局中4局で気象データが取得できていない。正しいコードに修正し、全局で JMA AMeDAS のリアルタイム気象データ（気温・降水量）が取得できる状態にする
確認: PHP 構文チェック、PHPStan

---

## 背景

JMA AMeDAS API (`https://www.jma.go.jp/bosai/amedas/data/map/{timestamp}.json`) の疎通テストにより、以下が判明:

- 6局は正常にデータ取得可能（気温 26〜29°C、夏季の実測値）
- 4局はコードが AMeDAS テーブルに存在せず NOT FOUND

## 修正内容

### src/app/Services/JmaApiService.php

`$stationToAmedas` マッピングの4局を修正:

| ステーション | 現コード | 正コード | 備考 |
|-------------|---------|---------|------|
| ST001 枚方 | `62056` | `62046` | amedastable.json で確認済み |
| ST007 宝塚（三田） | `62021` | `63411` | 同上 |
| ST009 五條 | `64081` | `64127` | 同上 |
| ST010 和歌山 | `65022` | `65042` | 同上 |

変更しない6局（正常動作済み）:
- ST002 守口: `62078`
- ST003 宇治: `61286`
- ST004 桂川: `61286`
- ST005 柏原: `62096`
- ST006 王寺: `64036`
- ST008 尼崎: `62078`

## 確認

- PHP 構文チェック
- PHPStan
- 検証手順に curl での全局データ取得確認を記載
