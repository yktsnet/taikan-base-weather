## デプロイ/起動時に migrate・seed が走らずデータが空になる

id: 30
skill: pr-workflow
branch-slug: deploy-run-migrate-seed
github_issue: 13
status: done
type: fix
対象: docker-compose.yml, .github/workflows/deploy.yml
内容: 本番(sv6)・ローカル(kw_up)とも DB マイグレーション/シーディングが自動実行されず、観測所データ・管理者ユーザが投入されないため、ダッシュボードの表が空になりログインもできない問題の修正
確認: docker-compose.yml / deploy.yml の構文・コマンド整合性の目視確認（実 DB への適用は user がローカル・本番で実施）

---

## 症状

- 本番 URL: ヘッダーと地図(Leaflet)は表示されるが、地図下の観測所カード一覧が出ない。「管理者ログイン」画面は出るがログイン後に遷移しない。
- アセット(JS/CSS)は本番では正常配信済み（`public/build/manifest.json` 存在、地図描画 OK）。よってアセット問題ではない。

## 根本原因

`DashboardController@index` は `Station::...->get()` を Inertia に渡し、フロントは `stations.map(...)` でカードを描画する。
本番 DB の `stations` が空のためカードが0枚になり、地図(固定中心)だけが表示される。
ログインも `Auth::attempt` が失敗し `auth.failed` を返すため画面に留まる（管理者ユーザ未投入）。

`database/seeders/DatabaseSeeder.php` は `StationSeeder`（観測所）と管理者ユーザ
`admin@example.com` / `password` を投入する設計だが、
**`php artisan migrate` / `db:seed` を実行する箇所が deploy.yml・docker-compose.yml・docker/ のどこにも無い**。
結果、空 DB のまま起動し続けている。

## 暫定対応（ops・本 Issue 実装とは別に user が即実行可）

```sh
docker exec kawa-watch-app php artisan migrate --force
docker exec kawa-watch-app php artisan db:seed --force
```

## 修正方針（案・実装者が最終判断）

起動時に migrate を確実に流し、初回のみ seed する形が無難。app サービス command 例:

```yaml
command: >
  sh -c "
  if [ ! -f vendor/autoload.php ]; then composer install; fi &&
  php artisan migrate --force &&
  php artisan db:seed --force &&
  php artisan serve --host=0.0.0.0 --port=8000
  "
```

- `db:seed` は冪等性に注意。`StationSeeder` が重複挿入しないか確認し、必要なら `updateOrCreate` 化、
  または「初回のみ seed」（stations が空のときだけ）に限定する。`DatabaseSeeder` のユーザ作成は既に `updateOrCreate` で冪等。
- 代替: deploy.yml の「Start application on server」ステップ後に
  `docker compose exec -T app php artisan migrate --force && ... db:seed --force` を追加する方式でも可。
  どちらか一方に統一する（二重実行を避ける）。
- migrate を起動コマンドに入れる場合、DB コンテナ起動完了前に走らないよう待ち（リトライ/healthcheck）を検討。

## 関連

- Issue 29: ローカル `kw_up` で Vite アセットがビルドされない問題（別系統だが、ローカルは本 Issue の seed 欠如と合わせて両方必要）。

## 確認

- docker-compose.yml / deploy.yml の構文・コマンド連結が破綻しないこと（目視）
- seed の冪等性（再起動・再デプロイで重複データや失敗が出ないこと）をコードで確認
- 実 DB への適用と画面確認（表が出る・`admin@example.com` でログインできる）は user が実施
