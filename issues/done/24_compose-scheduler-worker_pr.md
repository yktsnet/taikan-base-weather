Closes #2

## 変更内容

SV6 上で観測データ更新を全自動化するため、Laravel スケジューラとキューワーカーを docker-compose の常駐サービスとして追加する。NixOS cron / systemd には依存せず compose 完結とする。

### scheduler サービス
- `php artisan schedule:work` で Laravel スケジューラを常駐起動
- `bootstrap/app.php` で定義済みの `app:poll-water-level` / `app:poll-weather`（5分毎）が自動実行される
- `depends_on`: app, db, localstack

### worker サービス
- `php artisan app:bulk-queue-worker` で SQS ポーリング＋DB バルクインサートを常駐起動
- `depends_on`: app, db, localstack

### 共通設計
- 既存 `app` サービスのイメージ `kawa-watch-app` を再利用
- `./src:/var/www` マウント、`working_dir: /var/www` を踏襲
- `restart: unless-stopped` で常駐
- ポート公開なし（外部に晒さない）

## 静的確認結果

YAML 構文検証（`nix-shell -p yq-go --run "yq e '.' docker-compose.yml"`）: OK

変更ファイル:
```
docker-compose.yml
```

## 検証手順

1. `docker compose up -d --build` でコンテナを起動
2. `docker compose ps` で `kawa-watch-scheduler` と `kawa-watch-worker` が Running であることを確認
3. `docker compose logs -f scheduler` でスケジューラが `schedule:work` として起動し、5分毎に poller が実行されることを確認
4. `docker compose logs -f worker` で `app:bulk-queue-worker` が SQS ポーリングを行っていることを確認
5. ダッシュボード（`localhost:8093`）で水位・気象データが自動更新されることを確認
