## README を readme-guide 準拠へ刷新

id: 27
skill: pr-workflow
branch-slug: readme-standardization
github_issue:
status: draft
type: feat
対象: README.md
内容: README を readme-guide の構成・言語規則に沿って刷新する。CI/Deploy バッジ、Docker-first の Quick Start、Mermaid 構成図、選定理由付き Tech Stack を整える。完成済みアプリの公開向け README とする。
確認: 見出し言語規則（H1〜H3 は英語、本文・H4 以降は日本語）を満たすこと。バッジ/リンクの owner/repo が `yktsnet/kawa-watch` で正しいこと。インフラ実値（Tunnel UUID・サーバ絶対パス・Secrets・内部ポートマッピング）が混入していないことを目視確認。

---

> 保留中（draft）: 選定理由の正本となる JUDGE.md が別 PC にあるため、それを取り込める状態になってから実装する。`issue()` の対象から除外される。

## 構成（readme-guide §2 の順）

上から: H1＋バッジ＋概要 → Quick Start → Overview → Architecture → Tech Stack → Design Decisions → Scope → Deploy → Development →（任意）Directory Structure。

### 言語規則
- H1〜H3 は英語、本文・H4 以降・表の中身は日本語（readme-guide §1）。

### H1 + バッジ
```markdown
# kawa-watch

[![Testing & CI](https://github.com/yktsnet/kawa-watch/actions/workflows/test.yml/badge.svg)](https://github.com/yktsnet/kawa-watch/actions/workflows/test.yml)
[![Deploy to Production](https://github.com/yktsnet/kawa-watch/actions/workflows/deploy.yml/badge.svg)](https://github.com/yktsnet/kawa-watch/actions/workflows/deploy.yml)
```
1〜2行で「河川水位・気象モニタリング」を要約。

### Quick Start（Docker-first）
- `cp src/.env.docker src/.env`（または既存手順）→ `docker compose up -d --build` でアクセス先 `http://localhost:8093` まで。
- 言語ランタイム個別インストール（WSL/SQLite ネイティブ手順）は **Quick Start から外し Development へ移すか削除**する（readme-guide「Docker で動くなら Docker だけ先に」）。

### Overview
- 目的・背景・**Demo URL** を記載。Demo は公開アプリの URL `https://kawa-watch.ykts.net`（Cloudflare Tunnel 経由で公開済み。これは意図的な公開リンクで秘匿対象ではない）。

### Architecture
- 既存のテキスト構成図を **Mermaid** に書き換えてインライン埋め込み（poller → SQS(water/weather + DLQ) → BulkQueueWorker → MySQL / S3 アーカイブ / Inertia ダッシュボード）。

### Tech Stack / Design Decisions
- 表形式で技術＋選定理由。ただし **JUDGE.md は存在しない**ため、選定理由を創作しない（readme-guide §3）。自明な事実（Laravel+Inertia+React、SQS イベント駆動、LocalStack によるローカル AWS 模擬等）に基づく範囲で簡潔に書き、判断ログが要る箇所は将来 JUDGE.md 統合の余地として深掘りしない。

### Scope
- Focus（水位・気象の定期取得と可視化・アラート）と Out-of-Scope（本物の AWS 運用＝現状 LocalStack シミュレーション等）を明示。

### Deploy
- 方式の概要のみ。「main push → CI 通過後に自動デプロイ（compose 型）、Cloudflare Tunnel 経由で公開」程度。**Secrets 一覧・サーバ設定・絶対パスは書かない**（cicd-guide / 運用ドキュメントの責務）。

## 情報セキュリティ

- README に Tunnel UUID・cloudflared パス・sv6 絶対パス・Secrets 値・内部ポート（コンテナ内 8000 等）を書かない。
- 公開 URL（`kawa-watch.ykts.net`）と localhost:8093 は記載可。
