## 変更内容

SV6での本番稼働に向けて、Laravelの自動実行スケジュールを定義し、Redis/Mailpitの削除やポート競合回避などのDocker構成調整を行う。

### 1. src/bootstrap/app.php
- `Illuminate\Console\Scheduling\Schedule` のインポートを追加。
- `withSchedule` メソッドを追加し、5分おきに `app:poll-water-level` と `app:poll-weather` コマンドを実行するスケジュールを設定。

### 2. docker-compose.yml
- `redis` サービスおよび `mail`（Mailpit）サービスを削除。
- `app` の `depends_on` から `redis` を削除。
- `app` の公開ポートを `8000:8000` → `8093:8000` に変更（ポート競合回避）。
- `db` の公開ポートを `3306:3306` → `127.0.0.1:3307:3306` に変更（ホストMySQLとの競合回避）。
- `localstack` の公開ポートを `4566:4566` → `127.0.0.1:4567:4566` に変更（他サービスとの競合回避）。
- コンテナ間通信は引き続き `http://localstack:4566` を使用するため内部への影響なし。

### 3. src/.env.docker
- `SESSION_DRIVER=database` → `SESSION_DRIVER=file`（Redis不要化）
- `CACHE_STORE=redis` → `CACHE_STORE=file`（Redis不要化）
- `CACHE_DRIVER=file` を追加
- `QUEUE_CONNECTION=redis` → `QUEUE_CONNECTION=sync`（`app:bulk-queue-worker` が直接SQSをポーリングするため、Laravelデフォルトキューは sync に変更）

## 静的確認結果

### PHP シンタックス確認
ホスト環境に `php` コマンドが存在しないため `php -l` は実行不可。ファイルの内容はLaravel 11の `bootstrap/app.php` 標準パターンに沿っており、`use Illuminate\Console\Scheduling\Schedule;` インポートと `->withSchedule(...)` メソッドチェーンは公式APIに準拠。

### docker-compose.yml 構文確認
```
docker compose config --quiet → OK
```

### 変更ファイル一覧 (git diff --name-only)
```
docker-compose.yml
src/.env.docker
src/bootstrap/app.php
```

## 検証手順

1. `docker compose up -d --build` で起動。
2. `http://localhost:8093` にアクセスし、アプリが正常に起動することを確認。
3. `docker compose exec app php artisan schedule:list` を実行し、`app:poll-water-level` と `app:poll-weather` が5分おきのスケジュールで表示されることを確認。
4. `docker compose ps` で `redis` と `mail` コンテナが存在しないことを確認。
5. `docker compose exec app php artisan tinker` 内で `Cache::put('test', 1); Cache::get('test');` を実行し、fileキャッシュが正常動作することを確認。
