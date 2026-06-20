## 変更内容

本番・ローカルとも DB マイグレーション/シーディングが自動実行されず、観測所データ・管理者ユーザが投入されないため、ダッシュボードの表が空になりログインもできない問題を修正。

- `docker-compose.yml`: app サービスの起動コマンドに `php artisan migrate --force` と `php artisan db:seed --force` を追加。DB 起動待ちのリトライループ付き。
- `StationSeeder.php`: `DB::table()->insert()` を `upsert()` に変更し、再デプロイ・再起動時に重複エラーが起きないよう冪等化。

Closes #13

## 静的確認結果

- PHP 構文チェック: 全ファイル `No syntax errors detected`
- 変更ファイル:
  ```
  docker-compose.yml
  src/database/seeders/StationSeeder.php
  ```

## 検証手順

1. ローカルで DB ボリュームを削除して初期状態にする
   ```sh
   docker compose down -v
   ```
2. コンテナを起動
   ```sh
   docker compose up -d --build
   ```
3. app コンテナのログで migrate / seed が実行されたことを確認
   ```sh
   docker compose logs -f app
   ```
4. ブラウザで `http://localhost:8093` にアクセスし、地図下に観測所カード一覧が表示されることを確認
5. 「管理者ログイン」から `admin@example.com` / `password` でログインできることを確認
6. コンテナを再起動して重複エラーが出ないことを確認
   ```sh
   docker compose restart app
   docker compose logs -f app
   ```
