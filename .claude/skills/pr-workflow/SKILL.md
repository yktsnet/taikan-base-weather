---
name: pr-workflow
description: Issue駆動開発における実装・検証・PR作成の標準フロー
disable-model-invocation: true
---
以下の手順で割り当てられたIssueを実行する。
前提: Agentはコード編集とPR作成までを担当。動作確認・マージはuser様が行う。

0. `context/conventions.md` を読み、技術スタックと規約を把握する。
1. `issues/` ディレクトリ内の対象Issueファイル（status: open）を読み込む。
2. 実行環境（Claude Code または Jules）に応じたコンテキストを確認する。
   - Claude Codeの場合: ローカルブランチ `claude/{id}-{branch-slug}` 上にいることを認識。
   - Julesの場合: クラウドサンドボックス環境であり、ローカルブランク操作は不要であることを認識。
3. 対象ファイルに対して実装・修正を行う。
4. Issueの「確認」項目に従い静的チェックを実施する。
   - 変更したPHPファイルに対して `find src/app -name "*.php" | xargs -I{} php -l {}` を実行。
   - `docker` および `artisan` コマンドは実行禁止。
5. コミット対象の確認（Claude Code環境のみ）。
   - `git add {変更ファイル}` 後、`git diff --name-only --cached` を実行。
   - 出力がIssueの「対象」フィールドと完全一致することを確認。不一致時は実装を修正。
6. コミットの実行（Claude Code環境のみ）。
   - `git commit -m "{type}: {タイトル}"` を実行。
7. PRボディの作成。
   - `.git/pr_body.md` に以下の内容を書き出す。
   - Claude Code環境では、同内容を `issues/done/{id}_{branch-slug}_pr.md` にもコピーし、コミットに統合する。

   ## 変更内容
   {Issueの内容フィールドを展開}

   ## 静的確認結果
   {確認項目に対して実行した結果。git diff --name-only の出力を含む}

   ## 検証手順
   {実装内容から判断した、user様がローカルで確認するための手順}

8. PRの作成。
   - 以下コマンドを実行してプルリクエストを発行。
   `gh pr create --base main --title "{type}: {タイトル}" --body-file .git/pr_body.md`
9. 作成されたPRのURLを出力してタスクを終了。
