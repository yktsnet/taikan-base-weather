## Pollerのデータベースカラム不整合の修正
id: 04a
skill: pr-workflow
branch-slug: poller-fix
github_issue:
status: open
type: fix
対象: src/app/Models/Station.php (変更), src/app/Console/Commands/WeatherPoller.php (変更)
内容: StationモデルおよびWeatherPollerで使われている緯度・経度のプロパティ名（latitude, longitude）を、データベースのカラム名（lat, lng）に合わせて修正する。
確認: php -l による構文チェック、作成されたファイルパスの整合性の目視確認。
---
## 実装仕様

### 1. Stationモデルの修正
- **`src/app/Models/Station.php`**:
  - `$fillable` 配列の中身を、`['code', 'name', 'latitude', 'longitude']` から、マイグレーションのカラムに合わせる形で `['code', 'name', 'river_name', 'prefecture', 'lat', 'lng', 'warning_level', 'danger_level']` に修正する。

### 2. WeatherPollerコマンドの修正
- **`src/app/Console/Commands/WeatherPoller.php`**:
  - 62行目の `$latitude = $station->latitude ?? 35.0;` を、実際のデータベースカラムである `$latitude = $station->lat ?? 35.0;` に修正する。
