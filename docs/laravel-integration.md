# Laravel Backend Integration Examples

This guide shows practical backend setups for `snb4crazy/laravel-magic-link-auth`.

## 1) Install and migrate

```bash
composer require snb4crazy/laravel-magic-link-auth
# For bearer-token mode, if Sanctum is not already configured:
php artisan install:api
php artisan migrate
php artisan vendor:publish --tag=magic-link-auth-config
```

## 2) Basic API-only configuration

Set the important environment variables:

```dotenv
MAGIC_LINK_FRONTEND_URL=myapp://auth/magic-link
MAGIC_LINK_TTL_MINUTES=15
MAGIC_LINK_REQUIRE_VERIFIED_EMAIL=false
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=true
MAGIC_LINK_REVOKE_EXISTING_TOKENS=false
```

The package registers these routes by default:

- `POST /api/auth/magic-link/request`
- `POST /api/auth/magic-link/exchange`

## 3) Request and exchange endpoints (contract)

### Request link

`POST /api/auth/magic-link/request`

```json
{
  "email": "user@example.com"
}
```

Response is always generic (prevents user enumeration):

```json
{
  "message": "If that email is registered, a magic link has been sent."
}
```

### Exchange token

`POST /api/auth/magic-link/exchange`

```json
{
  "email": "user@example.com",
  "token": "token-from-email-link"
}
```

Success (token mode):

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com"
  },
  "token": "plain-text-api-token",
  "token_type": "Bearer"
}
```

Invalid/expired token:

```json
{
  "message": "The magic link is invalid or has expired."
}
```

## 4) Typical API auth wiring

Protect your own API endpoints with `auth:sanctum`:

```php
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', fn () => auth()->user());
});
```

Your user model should support token creation (for example, Sanctum `HasApiTokens`):

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
```

## 5) Optional: only allow verified emails

```dotenv
MAGIC_LINK_REQUIRE_VERIFIED_EMAIL=true
```

When enabled, unverified users get `403` from `/exchange` with:

```json
{
  "message": "Email verification required before magic-link login.",
  "email_verification_required": true
}
```

## 6) Optional: session mode instead of API token mode

If your app needs session auth instead of bearer tokens:

```dotenv
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=false
MAGIC_LINK_SESSION_GUARD=web
```

Then `/exchange` logs in via guard and returns:

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com"
  },
  "message": "Logged in successfully."
}
```

## 7) Production recommendations

- Keep a short TTL (10-20 minutes).
- Keep route throttling enabled (default config already does this).
- Always use HTTPS in production.
- Use a deep-link URL as `MAGIC_LINK_FRONTEND_URL` for mobile apps.
