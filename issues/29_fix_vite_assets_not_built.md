## サイトが部分的にしか表示されない（Vite アセット未ビルド）

id: 29
skill: pr-workflow
branch-slug: fix-vite-assets-not-built
github_issue: 11
status: open
type: fix
対象: docker-compose.yml
内容: ローカル（kw_up）起動時に Vite フロントエンドアセットがビルド・配信されず、Inertia/React 本体がマウントされない問題の修正
確認: docker-compose.yml の YAML 構文目視確認、起動コマンドの整合性確認（動作確認は user が Mac ローカルで実施）

---

## 背景・症状

サイトを URL から開いても、ローカル `kw_up` で開いても、ページが「部分的にしか表示されない」。

本プロジェクトは Laravel + Inertia + React + Vite 構成。`resources/views/app.blade.php` は
`@viteReactRefresh` / `@vite(['resources/js/app.jsx'])` / `@inertia` のみを持ち、
実際の画面は React アプリがマウントして描画する。

`@vite` ディレクティブは以下のいずれかを参照してアセットを解決する:
- 本番モード: `src/public/build/manifest.json`（`npm run build` の成果物）
- dev モード: `src/public/hot`（`npm run dev` 起動時のマーカー）

現状、ローカルではどちらも存在しない（`public/build` は `.gitignore` 対象かつ未生成、`hot` も無し）。
そのため HTML の殻だけがレスポンスされ、React/Inertia の中身がマウントされず「部分的にしか出ない」。

## 根本原因（ローカル）

`docker-compose.yml` の `app` サービスの起動コマンドが
`composer install`（vendor 欠如時）→ `php artisan serve` のみで、
フロントエンドのビルド（`npm ci && npm run build`）も dev サーバ（`npm run dev`）も一切起動しない。
Dockerfile に node/npm は同梱されているが未使用。結果 `public/build` が永遠に生成されない。

## 修正方針（案）

`docker-compose.yml` の `app` サービス command に、起動時のフロントエンドアセット用意を追加する。
ローカル開発用途なので、vendor と同様に「無ければ用意する」形が無難。

例（実装者が最終判断）:
```yaml
command: >
  sh -c "
  if [ ! -f vendor/autoload.php ]; then composer install; fi &&
  if [ ! -f public/build/manifest.json ]; then npm ci && npm run build; fi &&
  php artisan serve --host=0.0.0.0 --port=8000
  "
```

代替案として、HMR を活かすなら別サービスで `npm run dev`（`--host 0.0.0.0`）を常駐させる方法もあるが、
本番との差異を小さく保つためビルド成果物方式を推奨。

## スコープ外（別途確認）

本番 URL も同症状という報告について。`deploy.yml` は CI 上で `npm ci && npm run build` を実行し、
rsync の `--exclude` に `public/build` を含めていないため、ビルド成果物はサーバへ転送される設計になっている。
従って本番が同症状である場合は本 Issue（docker-compose のローカルギャップ）とは別要因の可能性がある:
- 「Testing & CI」未通過で deploy 未発火 / 最新のビルド込み deploy が未実行
- サーバ上の `src/public/build/manifest.json` 欠落

→ user 側でサーバの `src/public/build/manifest.json` 有無を確認し、欠落していれば deploy 再実行で切り分ける。

## 確認

- docker-compose.yml の YAML 構文（目視）
- app サービス command が composer/npm/artisan serve の順で破綻なく連結されていること
- 動作確認（`kw_up` 起動 → ブラウザで画面が完全表示されること）は user が Mac ローカルで実施
