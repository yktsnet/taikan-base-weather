# Queue Worker (非同期処理・アラート判定)の実装

## 完了したタスク
- `ProcessWaterLevelEvent` ジョブを作成し、水位データの受信とアラート判定、保存処理を実装しました。
- `ProcessWeatherEvent` ジョブを作成し、気象データの受信と保存処理を実装しました。
- `AlertNotification` メールクラスと blade テンプレートを作成し、管理者向け警告メール通知を実装しました。
- 全ての関連ファイルについて `php -l` による構文チェックをパスしました。
