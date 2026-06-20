# kawa-watch 開発規約

コードの書き方・編集の共通ルール（どう書くか）。ディレクトリ構成・データフローは `structure.md` を参照。

## 1. 技術スタック
- **バックエンド**: Laravel 11（Sail ベース、本番 Dockerfile 分離）
- **フロントエンド**: Inertia.js + React（Vite ビルド）
- **データベース**: RDS MySQL 8（Eloquent リレーションを厳格に設計）
- **キュー/非同期**: SQS + 独自 Queue Worker（EventBridge → ECS Scheduled Task から SQS 投入のイベント駆動）
- **IaC**: Terraform（HCL による一元管理）

## 2. コードスタイル
- PHP は PSR-12 準拠。`./vendor/bin/pint` でフォーマットを統一する。
- 静的解析は PHPStan（`phpstan.neon` の設定に従う）。
- Eloquent モデルのリレーションは明示的に定義し、N+1 を避ける。

## 3. ファイル編集戦略
- **広範囲の書き換え**: 変更箇所が多い場合（目安: 10箇所以上、またはファイルの20%超）、`str_replace` の繰り返しではなく `bash` でファイル全体を一括書き出す（`cat > path << 'EOF'` 等）。
- **局所的修正**: 数行以内の修正に限定してツールを使用。
- **静的チェック**: PHP ファイル変更時は、ホスト側で以下を実行しシンタックスを確認。
  ```bash
  find src/app -name "*.php" | xargs -I{} php -l {}
  ```
- **Nix 環境前提**: ホストは Nix 管理。`pip install` / `npm install -g` 等のグローバルインストールは禁止（環境を汚す）。標準で入っていないツールが要る場合のみ、使い捨てシェル `nix-shell -p {pkg} --run "..."` で実行する。YAML/compose の検証は基本目視、必要なら `nix-shell -p yq-go`。
