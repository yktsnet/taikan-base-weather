## 変更内容

ローカル（kw_up）起動時に Vite フロントエンドアセットがビルド・配信されず、Inertia/React 本体がマウントされない問題の修正。

`docker-compose.yml` の `app` サービスの起動コマンドに、`public/build/manifest.json` が存在しない場合に `npm ci && npm run build` を実行するステップを追加。これにより初回起動時にフロントエンドアセットが自動的にビルドされ、React アプリが正しくマウントされるようになる。

Closes #11

## 静的確認結果

- PHP ファイルの変更なし（静的解析不要）
- YAML 構文: 目視確認 OK
- command 連結: `composer install`（条件付き）→ `npm ci && npm run build`（条件付き）→ `php artisan serve` の順で整合

```
変更ファイル:
docker-compose.yml
```

## 検証手順

1. `kw_up` でコンテナを起動
2. 初回起動時、`app` コンテナのログに `npm ci` → `npm run build` の実行が表示されることを確認
3. ブラウザで localhost:8093 にアクセスし、React/Inertia のダッシュボードが完全に表示されることを確認
4. 2回目以降の起動で `public/build/manifest.json` が既に存在する場合、npm ビルドがスキップされることを確認
