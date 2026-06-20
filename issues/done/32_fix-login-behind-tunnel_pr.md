## 変更内容

Cloudflare Tunnel (HTTPS) 経由のアクセスでセッション/CSRF が壊れ、ログイン後の redirect が機能しない問題を修正。

### 根本原因

cloudflared が公開 URL (HTTPS) → `http://localhost` にプロキシしているが、Laravel 側に TrustProxies ミドルウェアが未設定のため:
- `$request->secure()` が `false` を返す
- CSRF cookie の整合性が取れない
- `redirect()->intended()` が `http://` URL を生成し、ブラウザの mixed-content ポリシーでブロックされる

### 修正内容

1. **`src/bootstrap/app.php`**: `trustProxies(at: '*')` を追加し、全プロキシからの `X-Forwarded-*` ヘッダを信頼するよう設定。Cloudflare Tunnel 経由のため `*` で安全。
2. **`src/.env.docker`**: `APP_URL` を公開 HTTPS URL に変更。リクエスト外コンテキスト（artisan / queue）での URL 生成を正しくする。

`SESSION_DOMAIN` は `null` のまま維持（localhost・トンネル経由いずれでも cookie が有効になるよう）。`SESSION_SECURE_COOKIE` も未設定のまま（localhost HTTP アクセスとの両立）。TrustProxies により HTTPS リクエスト時は自動的に正しく動作する。

## 静的確認結果

PHP 構文チェック（`find src/app -name "*.php" | xargs php -l`）: 全ファイル No syntax errors
`php -l src/bootstrap/app.php`: No syntax errors

変更ファイル:
```
src/.env.docker
src/bootstrap/app.php
```

## 検証手順

1. `docker compose up -d` でコンテナを起動
2. 公開 URL (`https://<KAWA_SUBDOMAIN>`) でログインページにアクセス
3. ログイン → `/admin/verification` に遷移することを確認
4. ページリロード後もログイン状態が維持されることを確認
5. `localhost:<KAWA_PORT>` でもログインが正常に動作することを確認
