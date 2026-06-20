## 変更内容

cicd-guide の compose 型に従い、main の CI（"Testing & CI"）通過後に自動デプロイする `deploy.yml` を追加。

- `workflow_run` トリガーで "Testing & CI" 成功時のみ deploy ジョブを実行
- フロントエンドを CI 内でビルド（Node 24 + `npm ci` → `npm run build`）し `src/public/build` を同梱
- Tailscale 経由で対象サーバへ接続（`tailscale/github-action@v3`）
- rsync でソースを転送（`node_modules` / `.git` / `vendor` / `src/.env` 等を除外）
- SSH で `docker compose up -d --build` を実行しコンテナを起動

### 必要な GitHub Secrets

| Secret | 用途 |
|---|---|
| `DEPLOY_HOST` | デプロイ先ホスト（Tailscale 経由） |
| `DEPLOY_USER` | デプロイ先ユーザー |
| `SSH_PRIVATE_KEY` | デプロイ先への SSH 秘密鍵 |
| `TS_OAUTH_CLIENT_ID` | Tailscale OAuth Client ID |
| `TS_OAUTH_SECRET` | Tailscale OAuth Secret |

## 静的確認結果

- `actionlint .github/workflows/deploy.yml` → エラーなし
- YAML 構造を目視確認済み
- `${{ secrets.* }}` 参照に実値の混入なし
- 転送先パスはホーム相対（`~/projects/kawa-watch`）

```
変更ファイル:
.github/workflows/deploy.yml (新規)
issues/done/26_cicd-deploy_pr.md (新規)
```

## 検証手順

1. GitHub リポジトリに上記 5 Secrets を登録する
2. Tailscale ACL に `tag:ci` を許可する
3. デプロイ先サーバに `src/.env`（本番値）と Docker を用意し、`~/projects/kawa-watch` を配置先にする
4. main へ push し、"Testing & CI" が成功 → "Deploy to Production" が自動実行されることを確認
5. デプロイ先サーバで `docker ps` を実行し、コンテナが起動していることを確認

Closes #6
