## Pint スタイル違反を修正し CI を緑にする

id: 28
skill: pr-workflow
branch-slug: pint-style-fix
github_issue:
status: open
type: cleanup
対象: src/app/Http/Controllers/Admin/VerificationController.php, src/app/Services/SqsQueueService.php, src/database/seeders/DatabaseSeeder.php, src/routes/web.php
内容: CI（Testing & CI）の `./vendor/bin/pint --test` が4ファイルのスタイル違反で失敗し、その結果デプロイが skipped のままになっている。Pint で自動整形し CI を通す。
確認: `./vendor/bin/pint --test` がクリーン（違反0）で完了すること。整形以外の差分が混入していないこと（`git diff` 目視で振る舞い変更が無いこと）。

---

## 背景

main の CI が赤で、`deploy.yml`（workflow_run, CI 成功条件）が永久に skipped になっている。テストではなく **Pint のコードスタイル**違反が原因。

CI ログで検出された違反:

| ファイル | 主なルール |
|---|---|
| `app/Http/Controllers/Admin/VerificationController.php` | concat_space, trailing… |
| `app/Services/SqsQueueService.php` | no_superfluous_phpdoc_tags, unary_operator… |
| `database/seeders/DatabaseSeeder.php` | fully_qualified_strict_types, ordered… |
| `routes/web.php` | ordered_imports |

## 手順

1. `src/` で `./vendor/bin/pint` を実行（`--test` なし＝自動修正）。vendor が無ければ `composer install` で用意（composer はプロジェクトローカル `vendor/` に入る。グローバル導入はしない）。
2. `./vendor/bin/pint --test` で違反0を確認。
3. 整形のみの差分であることを `git diff` で確認（ロジック変更なし）。

## 注意
- Pint の設定は既存（`pint.json` があればそれ、無ければ Laravel プリセット）に従う。ルールは変更しない。
