## AWSインフラ基本コンポーネントのコード化
id: 01
skill: pr-workflow
branch-slug: terraform-base
github_issue:
status: close
type: feat
対象: terraform/main.tf (新規), terraform/variables.tf (新規), terraform/outputs.tf (新規)
内容: PLAN.mdのPhase 1に基づく、VPC、RDS、SQS、S3の基本インフラの定義
確認: ttfmtによるフォーマット確認、tflintによる静的解析（ホスト側実行）、コード目視確認

---

## 実装仕様

PLAN.mdに定義されたアーキテクチャのうち、基盤となるネットワークとデータストア、キューのTerraformコードを作成する。ECSやEventBridge、ALBはアプリコード確定後に調整するため、本Issueのスコープ外とする。

### 1. ネットワーク (VPC & Subnets)
- VPC: `10.0.0.0/16`
- アベイラビリティゾーン: `ap-northeast-1a`, `ap-northeast-1c` の2面
- サブネット構成:
  - Public Subnet × 2 (ALB/NAT共通用)
  - Private Subnet × 2 (ECS App/Worker用)
  - Isolated Subnet × 2 (RDS用)
- 必要なルートテーブル、インターネットゲートウェイの定義

### 2. データベース (RDS MySQL)
- 識別子: `kawa-watch-db`
- エンジン: MySQL 8.0系
- インスタンスクラス: `db.t4g.micro`（コスト最優先）
- 配置: Isolated Subnet（マルチAZは不要、シングル構成）
- セキュリティグループ: Private Subnet（ECS）からの3306ポートへのアクセスのみ許可

### 3. キュー (SQS)
- メインキュー: `kawa-watch-raw-events` (Standard Queue)
- デッドレターキュー (DLQ): `kawa-watch-raw-events-dlq`
- 最大受信数 (maxReceiveCount): 3回

### 4. ストレージ (S3)
- バケット名: `kawa-watch-csv-archive-${var.environment}`
- パブリックアクセスブロック: すべて有効（完全プライベート）

## 成果物確認（Agent実行）
- 新規作成したterraformディレクトリ内での構文チェック
- 起動制限に基づき、`terraform init` や `terraform plan` などのAWSプロバイダ接続が発生するコマンドは実行しないこと（コード書き出しと目視確認に徹する）
