# Laravel Magic Link Auth

[![License: MIT](https://img.shields.io/github/license/snb4crazy/laravel-magic-link-auth)](LICENSE)
[![GitHub release](https://img.shields.io/github/v/release/snb4crazy/laravel-magic-link-auth)](https://github.com/snb4crazy/laravel-magic-link-auth/releases)
[![GitHub stars](https://img.shields.io/github/stars/snb4crazy/laravel-magic-link-auth?style=social)](https://github.com/snb4crazy/laravel-magic-link-auth/stargazers)
[![GitHub last commit](https://img.shields.io/github/last-commit/snb4crazy/laravel-magic-link-auth)](https://github.com/snb4crazy/laravel-magic-link-auth/commits/main)

Reusable Laravel package for passwordless "magic link" login, designed for API backends (including Laravel Sanctum token issuing).

## Integration guides

- [Laravel backend integration examples](docs/laravel-integration.md)
- [Flutter/mobile app integration examples](docs/flutter-mobile-integration.md)

## Features

- `POST /api/auth/magic-link/request` to request a one-time login link
- `POST /api/auth/magic-link/exchange` to exchange link token for authenticated payload
- One-time token consumption with expiry window
- Enumeration-safe request response (same success message for known/unknown emails)
- Configurable route prefix, middleware, TTL, lookup model/column, and frontend/deep-link URL
- Optional verified-email requirement and optional existing-token revocation

## Installation

```bash
composer require snb4crazy/laravel-magic-link-auth
php artisan migrate
```

Publish package config when you need customization:

```bash
php artisan vendor:publish --tag=magic-link-auth-config
```

## Configuration

Default file: `config/magic-link-auth.php`

```dotenv
MAGIC_LINK_FRONTEND_URL=myapp://auth/magic-link
MAGIC_LINK_TTL_MINUTES=15
MAGIC_LINK_EXCHANGE_CANDIDATE_LIMIT=5
MAGIC_LINK_REQUIRE_VERIFIED_EMAIL=false
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=true
MAGIC_LINK_REVOKE_EXISTING_TOKENS=false
```

### Important options

- `MAGIC_LINK_FRONTEND_URL`: Link base URL included in email (`email` + `token` query params are appended).
- `MAGIC_LINK_EXCHANGE_CANDIDATE_LIMIT`: Limits how many latest active tokens are checked on exchange (performance tuning).
- `MAGIC_LINK_ISSUE_SANCTUM_TOKEN`: When `true`, the package returns `token` + `token_type` if user model supports `createToken`.
- `MAGIC_LINK_REQUIRE_VERIFIED_EMAIL`: When `true`, unverified users receive `403` during exchange.

## API Contract

### Request magic link

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

### Exchange magic link

`POST /api/auth/magic-link/exchange`

```json
{
  "email": "user@example.com",
  "token": "64-char-token-from-link"
}
```

Session mode response:

```json
{
  "user": { "...": "..." },
  "message": "Logged in successfully."
}
```

Token mode response:

```json
{
  "user": { "...": "..." },
  "token": "plain-text-api-token",
  "token_type": "Bearer"
}
```

## Integration Notes

### Laravel backend integration

- Ensure your user model is configured in `auth.providers.users.model`.
- If using token mode, your user model must expose `createToken(...)` (for example via Sanctum's `HasApiTokens`).
- Named routes are available: `magic-link-auth.request` and `magic-link-auth.exchange`.

### Mobile/Flutter deep-link integration

Set `MAGIC_LINK_FRONTEND_URL` to your app deep link (for example `myapp://auth/magic-link`). The package appends `email` and `token`, which your app can read and submit to `/exchange`.

## Development and testing

Run the package test suite:

```bash
composer test
```
