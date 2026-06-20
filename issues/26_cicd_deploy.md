## CI/CD 自動デプロイ（compose 型・sv6）

id: 26
skill: pr-workflow
branch-slug: cicd-deploy
github_issue:
status: open
type: feat
対象: .github/workflows/deploy.yml (新規)
内容: cicd-guide の compose 型に従い、main の CI 通過後に sv6 へ自動デプロイする deploy.yml を追加する。フロントは CI 内でビルドして同梱する（todo 2-3）。
確認: actionlint で workflow 構文を確認（`nix-shell -p actionlint --run "actionlint .github/workflows/deploy.yml"`、pip 禁止）。YAML 構造を目視。secrets 参照（`${{ secrets.* }}`）に実値が混入していないことを目視確認。

---

## 方針（cicd-guide §3-1 compose 型）

既存 `test.yml`（"Testing & CI"）の成功後にデプロイを走らせる。GitHub Actions → Tailscale → scp → `docker compose up -d --build`。

## .github/workflows/deploy.yml（新規）

### トリガー（CI 通過後に限定）
```yaml
on:
  workflow_run:
    workflows: ["Testing & CI"]
    types: [completed]
    branches: [main]
```
`deploy` ジョブは `if: ${{ github.event.workflow_run.conclusion == 'success' }}` で成功時のみ実行する。

### ステップ
1. `actions/checkout@v4`
2. フロントビルド: `actions/setup-node@v4`（node 24, cache npm）→ `./src` で `npm ci` → `npm run build`（`src/public/build` を生成）
3. Tailscale 接続: `tailscale/github-action@v3`（`oauth-client-id` / `oauth-secret`, `tags: tag:ci`）
4. ソース転送: sv6 の `~/projects/kawa-watch/` へ同期。`node_modules` / `.git` / `vendor` は除外し、ビルド済み `src/public/build` は含める。
5. 起動: ssh で `cd ~/projects/kawa-watch && docker compose up -d --build`

### 必要な GitHub Secrets（cicd-guide §5）
| Secret | 用途 |
|---|---|
| `DEPLOY_HOST` | デプロイ先ホスト（Tailscale 経由） |
| `DEPLOY_USER` | デプロイ先ユーザー |
| `SSH_PRIVATE_KEY` | デプロイ先への SSH 秘密鍵 |
| `TS_OAUTH_CLIENT_ID` | Tailscale OAuth Client ID |
| `TS_OAUTH_SECRET` | Tailscale OAuth Secret |

## 情報セキュリティ

- ホスト実値・SSH ユーザ名・本番絶対パスは workflow に直書きせず、必ず `${{ secrets.* }}` を使う。転送先は `~/projects/kawa-watch`（ホーム相対）で書き、`/home/<user>` の絶対パスは使わない。
- PR 本文・コミットにも実値を書かない（必要時は secrets-agents のプレースホルダ）。

## 検証手順（user 実施）

- GitHub リポジトリに上記5 Secrets を登録。
- Tailscale ACL に `tag:ci` を許可。
- sv6 側に `src/.env`（本番値）と Docker を用意し、`~/projects/kawa-watch` を配置先にする。
- main へ push し、"Testing & CI" 成功 → deploy 実行 → sv6 上でコンテナ起動を確認（公開は Issue 27、ローカル確認は将来の `kw_up`）。
