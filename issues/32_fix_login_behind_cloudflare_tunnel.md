## Cloudflare Tunnel 経由でログインが遷移しない

id: 32
skill: pr-workflow
branch-slug: fix-login-behind-tunnel
status: open
type: fix
対象: bootstrap/app.php, .env.docker
内容: Cloudflare Tunnel (HTTPS) 経由のアクセスでセッション/CSRF が壊れ、ログイン後の redirect が機能しない問題の修正
確認: 構文確認 + 実環境での確認は user が実施

---

## 症状

- `localhost:8093` でのログインは安定する
- Cloudflare Tunnel 経由の公開 URL ではログインボタンが反応したりしなかったりし、成功しても遷移しない

## 根本原因

sv6 の cloudflared が公開 URL (HTTPS) → `http://localhost:8093` にプロキシしている。
しかし Laravel 側に以下の設定が欠けている:

### 1. TrustProxies ミドルウェア未設定

`bootstrap/app.php` の `withMiddleware` が空。Laravel はリクエストを直接の HTTP と認識し:
- `$request->secure()` が `false` を返す
- CSRF cookie に `Secure` フラグが付かない / HTTPS 側と不一致
- `redirect()->intended()` が `http://` URL を生成し、ブラウザの mixed-content ポリシーでブロックされる

### 2. APP_URL が localhost のまま

`.env.docker` で `APP_URL=http://localhost:8000`。
Laravel の URL 生成・リダイレクト先がすべて localhost ベースになる。

### 3. SESSION_DOMAIN=null

セッション cookie のドメインが未指定のため、公開ドメインとの不一致でセッションが維持されない可能性がある。

## 修正方針（案・実装者が最終判断）

### bootstrap/app.php

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
})
```

`*` で全プロキシを信頼（Cloudflare Tunnel 経由のため安全）。
これにより `X-Forwarded-Proto: https` が反映され、セッション/CSRF/リダイレクトが HTTPS ベースで動作する。

### .env.docker

```
APP_URL=https://kawa-watch.ykts.net
SESSION_DOMAIN=.kawa-watch.ykts.net
SESSION_SECURE_COOKIE=true
```

ただしローカル開発では localhost なので、環境変数で切り替えるか `.env.docker` はデプロイ向けに特化する判断が必要。

## 関連

- Issue 31: APP_KEY 生成・LocalStack init 修正（マージ済み）
- cloudflared tunnel 設定: `devices/gui/sv6/system.nix`

## 確認

- 公開 URL でログイン → `/admin/verification` に遷移すること
- localhost:8093 でも引き続きログインできること
- セッションが維持され、リロード後もログイン状態が保たれること
- 実環境での確認は user が実施
