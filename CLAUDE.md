# Claude Code指示書 (憲法)

Claude Codeは本ファイルを最優先の指示として実行すること。

## 1. 動作フローとインプット
- 起動時に `issues/` 内の対象Issue（`status: open`）を確認すること。
- 実装開始前に、必ず `context/conventions.md` を読み、規約を遵守すること。
- ローカル環境にて、`claude/{id}-{branch-slug}` ブランチ上で作業していることを認識すること。

## 2. 実行制限・禁止事項
- **禁止**: `docker` コマンド、`php artisan` コマンドの実行（コンテナ操作・マイグレーションはuser様の責務）。
- **許可**: `php -l` によるPHPファイルのシンタックス確認。

## 3. 成果物およびPR作成
- コミット前に `git diff --name-only --cached` を実行し、Issueの「対象」フィールドと完全一致することを確認すること。
- PRボディを `.git/pr_body.md` に書き出し、同内容を `issues/done/{id}_{branch-slug}_pr.md` にもコピー。
- 上記ファイルをアドしてコミットに統合後、`gh pr create --base main --title "{type}: {タイトル}" --body-file .git/pr_body.md` を実行すること。
