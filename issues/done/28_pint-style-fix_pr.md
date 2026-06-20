## 変更内容

CI（Testing & CI）の `./vendor/bin/pint --test` が4ファイルのスタイル違反で失敗し、デプロイが skipped のままになっていた問題を修正。Pint による自動整形を適用し CI を通す。

対象ファイルと適用ルール:

| ファイル | 適用ルール |
|---|---|
| `app/Http/Controllers/Admin/VerificationController.php` | concat_space, trailing_comma_in_multiline, not_operator_with_successor_space, no_whitespace_in_blank_line |
| `app/Services/SqsQueueService.php` | no_superfluous_phpdoc_tags, unary_operator_spaces, phpdoc_trim, not_operator_with_successor_space |
| `database/seeders/DatabaseSeeder.php` | fully_qualified_strict_types, ordered_imports |
| `routes/web.php` | ordered_imports |

Closes #8

## 静的確認結果

- `./vendor/bin/pint --test` → passed（違反0）
- PHP 構文チェック → 全ファイル No syntax errors detected

```
src/app/Http/Controllers/Admin/VerificationController.php
src/app/Services/SqsQueueService.php
src/database/seeders/DatabaseSeeder.php
src/routes/web.php
```

## 検証手順

1. `docker compose up -d` で開発環境を起動
2. `docker compose exec app ./vendor/bin/pint --test` を実行し、違反が0件であることを確認
3. GitHub 上で CI（Testing & CI）が緑になることを確認
4. CI 成功後に deploy.yml が skipped → success に変わることを確認
