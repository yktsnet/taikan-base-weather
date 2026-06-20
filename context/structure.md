# kawa-watch ディレクトリ構造

どこに何があるか。コードの書き方（規約）は `conventions.md` を参照。

## トップレベル

```
kawa-watch/
├── src/              # Laravel 11 本体
├── terraform/        # IaC（AWS リソース定義）
├── docker-compose.yml # ローカル/本番コンテナ構成
├── context/          # Agent 向け共通コンテキスト（本ファイル群）
└── issues/           # ローカル Issue 管理（done/ に完了分と PR 控え）
```

## src/（Laravel）

```
src/
├── app/
│   ├── Console/Commands/   # poller・worker・バッチの artisan コマンド
│   │   ├── WaterLevelPoller.php   # app:poll-water-level（水位ポーリング）
│   │   ├── WeatherPoller.php       # app:poll-weather（気象ポーリング）
│   │   ├── BulkQueueWorker.php     # app:bulk-queue-worker（SQS→DB バルク投入）
│   │   ├── ArchiveWaterLevelToS3.php # S3 アーカイブバッチ
│   │   └── LoadTestPoller.php      # 負荷試験用
│   ├── Http/Controllers/   # Inertia ページ・管理 API のコントローラ
│   ├── Jobs/               # キュー投入されるジョブ
│   ├── Models/             # Eloquent モデル
│   ├── Services/           # 外部 API（国交省・気象庁）連携・ドメインロジック
│   └── Mail/               # 通知メール
├── routes/
│   ├── web.php             # Web/Inertia ルート
│   └── console.php         # スケジュール・コンソールルート
├── bootstrap/app.php       # アプリ初期化（ミドルウェア・スケジュール定義）
├── resources/              # React(Inertia) フロント・Blade・CSS
├── config/                 # Laravel 設定
├── database/               # マイグレーション・seeder・factory
├── tests/                  # Pest テスト
└── Dockerfile              # 本番用コンテナ
```

## データフロー

```
EventBridge → ECS Scheduled Task → SQS 投入
                                      ↓
poller コマンド（水位/気象 API 取得） → SQS
                                      ↓
BulkQueueWorker（SQS ポーリング） → MySQL バルクインサート
                                      ↓
Inertia(React) ダッシュボード ← Controller ← Eloquent(MySQL)
                                      ↓
ArchiveWaterLevelToS3（定期バッチ） → S3 アーカイブ
```

## レイヤー構成

- **取得層**: `Console/Commands` の poller が外部 API（国交省 水文水質・気象庁）をポーリングし SQS へ投入。
- **処理層**: `BulkQueueWorker` が SQS を独自ポーリングし、MySQL へバルク書き込み（Laravel デフォルトキューは未使用）。
- **表示層**: `Http/Controllers` + Inertia.js/React。Eloquent 経由で MySQL を読み出しダッシュボード描画。
- **永続層**: MySQL（実測データ）＋ S3（アーカイブ）。
- **IaC**: `terraform/` が SQS・S3・RDS 等を定義。

## issues/

- `{NN}_{slug}.md`: 実装対象 Issue。`status: open` のものを Agent が処理。
- `00_template.md`: Issue ひな形。
- `done/`: 完了 Issue と PR 控え（`{id}_{slug}_pr.md`）。
