# CLAUDE.md

@context/conventions.md
@context/structure.md

Claude Code は本ファイルを最優先の指示として実行すること。

## 動作フロー
- 起動時に `issues/` 内の対象 Issue（`status: open`）を確認する。
- 実装開始前に `context/conventions.md` と `context/structure.md` を読み、規約と構造を把握する。
- ローカル環境にて `claude/{id}-{branch-slug}` ブランチ上で作業していることを認識する。
- 実装・検証・PR 作成は `.claude/skills/pr-workflow/SKILL.md` の手順に従う。

## コマンド
- PHP 構文チェック: `find src/app -name "*.php" | xargs -I{} php -l {}`
- コードスタイル: `./vendor/bin/pint --test`
- 静的解析: `./vendor/bin/phpstan analyse`

## 検証手段
- PR 前の Agent 側確認は構文チェック・静的解析まで。
- 動作確認（Docker 起動・ブラウザ確認）は user が Mac ローカルで実施。手順は PR の `## 検証手順` に記載する。

> 禁止・強制（docker / php artisan / git push 等の遮断）は `.claude/settings.json` の deny で管理する。本ファイルには書かない。
