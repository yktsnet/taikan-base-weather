## Scheduler/Worker の常駐サービス化（compose 完結）

id: 24
skill: pr-workflow
branch-slug: compose-scheduler-worker
github_issue: 2
status: close
type: feat
対象: docker-compose.yml
内容: SV6 上で観測データ更新を全自動化するため、Laravel スケジューラとキューワーカーを docker-compose の常駐サービスとして追加する。NixOS cron / systemd には依存せず compose 完結とする。
確認: docker-compose.yml の YAML 構造（インデント・サービス定義）を目視確認し、既存 app/db/localstack 定義と整合していること。機械チェックが必要な場合のみ Nix 流の使い捨てシェル `nix-shell -p yq-go --run "yq e '.' docker-compose.yml"` を使う（`pip install` は禁止）。

---

## docker-compose.yml

既存の `app` イメージ（`kawa-watch-app`）を再利用し、以下2サービスを追加する。どちらも `restart: unless-stopped` で常駐させる。

### scheduler サービス
- Laravel スケジューラを常駐起動：`php artisan schedule:work`
- これにより `bootstrap/app.php` で定義済みの `app:poll-water-level` / `app:poll-weather`（5分毎）が自動実行される。
- `depends_on`: `app`（イメージビルド）, `db`, `localstack`

### worker サービス
- SQS ポーリング＋DB バルクインサートを行う `app:bulk-queue-worker` を `--once` なしで常駐起動。
- `depends_on`: `app`, `db`, `localstack`

### 実装メモ
- 両サービスとも `image: kawa-watch-app` を参照し、`./src` を `/var/www` にマウント、`working_dir: /var/www` を踏襲する（app と同条件）。
- ポート公開は不要（外部に晒さない）。
- LocalStack はオンプレ SV6 上でもシミュレーション運用を継続する方針のため、接続先はコンテナ間通信 `http://localstack:4566` のまま。
