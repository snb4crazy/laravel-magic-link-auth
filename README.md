# Laravel Magic Link Auth

Reusable Laravel package for passwordless "magic link" login, designed for API backends (including Laravel Sanctum token issuing).

## Features

- `POST /api/auth/magic-link/request` to request login link by email
- `POST /api/auth/magic-link/exchange` to exchange token for auth session/token
- One-time token consumption with expiration
- Non-enumerating request response (always generic success message)
- Configurable route prefix, TTL, model/email column, and frontend/deep-link URL
- Sanctum token issuing support (optional)

## Install

```bash
composer require snb4crazy/laravel-magic-link-auth
php artisan migrate
```

Optional config publish:

```bash
php artisan vendor:publish --tag=magic-link-auth-config
```

## Environment example

```dotenv
MAGIC_LINK_FRONTEND_URL=myapp://auth/magic-link
MAGIC_LINK_TTL_MINUTES=15
MAGIC_LINK_REQUIRE_VERIFIED_EMAIL=false
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=true
```

## API contract

### Request a link

`POST /api/auth/magic-link/request`

```json
{
  "email": "user@example.com"
}
```

Always returns:

```json
{
  "message": "If that email is registered, a magic link has been sent."
}
```

### Exchange link token

`POST /api/auth/magic-link/exchange`

```json
{
  "email": "user@example.com",
  "token": "64-char-token-from-link"
}
```

Sanctum mode response:

```json
{
  "user": { "...": "..." },
  "token": "plain-text-api-token",
  "token_type": "Bearer"
}
```

## Notes for Flutter

Set `MAGIC_LINK_FRONTEND_URL` to your deep link, for example `myapp://auth/magic-link`. The package appends `email` and `token` query params, so the app can extract them and call `/exchange`.
