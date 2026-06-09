# kawa-watch 共通開発規約

本プロジェクトにおける技術選定根拠および実装・ファイル編集の共通ルール。

## 1. 技術スタック・アーキテクチャ
- **バックエンド**: Laravel 11 (Sailベース、本番Dockerfile分離)
- **フロントエンド**: Inertia.js + React (Viteビルド)
- **データベース**: RDS MySQL 8 (Eloquentリレーションを厳格に設計)
- **キュー/非同期**: SQS + Laravel Queue Worker (EventBridge → ECS Scheduled TaskからSQS投入のイベント駆動パターン)
- **IaC**: Terraform (HCLによる一元管理)

## 2. ファイル編集戦略
- **広範囲の書き換え**: 変更箇所が多い場合（目安: 10箇所以上、またはファイルの20%超）、`str_replace` の繰り返しではなく `bash` でファイル全体を一括書き出す（`cat > path << 'EOF'` 等）。
- **局所的修正**: 数行以内の修正に限定してツールを使用。
- **静的チェック**: PHPファイル変更時は、ホスト側で以下を実行しシンタックスを確認。
  ```bash
  find src/app -name "*.php" | xargs -I{} php -l {}
  ```

## 3. ディレクトリ構造
src/: Laravel本体

docker/: 開発・本番用コンテナ設定

context/: エージェント向け共通コンテキスト

issues/: ローカルIssue管理

---

### 運用のポイント
* `issue` コマンド実行時、Jules/Codeは自動的にそれぞれのルートにある `.md` を読み込む。
* Issueファイル側の「内容」「確認」に、API仕様（国交省・気象庁）の調査タスクや具体的な実装コードの指示を記述すれば、両Agentは上記の制約（破壊的コマンドの禁止、規約の遵守、指定フォーマットでのPR作成）を維持したまま、Issueの記述通りに自律駆動する。
