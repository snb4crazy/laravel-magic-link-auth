# Laravel Web App Integration (Session/Cookie Auth)

This guide shows how to use `snb4crazy/laravel-magic-link-auth` in a traditional Laravel web app with session authentication (not bearer tokens).

## Overview

Flow:

1. User opens your login page and submits email.
2. Your app posts to your web controller route (`/login/magic-link/request`).
3. User receives email and opens your web URL with `email` + `token`.
4. Your app posts `email` + `token` to your web controller route (`/login/magic-link/exchange`).
5. Package logs user in via Laravel `web` guard and returns authenticated user payload.
6. Browser keeps session cookie and user is authenticated on subsequent web requests.

## 1) Install and publish config

```bash
composer require snb4crazy/laravel-magic-link-auth
php artisan migrate
php artisan vendor:publish --tag=magic-link-auth-config
```

## 2) Configure for web session mode

In your `.env`:

```dotenv
APP_URL=https://app.example.com

# Where users land after clicking email link (web route/page in your app)
MAGIC_LINK_FRONTEND_URL=${APP_URL}/magic-link

# Disable token mode, enable session guard login
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=false
MAGIC_LINK_SESSION_GUARD=web
```

In published `config/magic-link-auth.php`, use a stateful middleware stack:

```php
'route_middleware' => ['web'],
```

Why this matters:

- The default middleware is `['api']`, which is usually stateless.
- Session login requires cookie/session middleware from `web`.
- CSRF protection will apply to `/request` and `/exchange` in this mode.

## 3) Endpoint URLs

The package routes are:

- `POST /<route_prefix>/request`
- `POST /<route_prefix>/exchange`

Default `route_prefix` is `api/auth/magic-link`, so full URLs are:

- `POST https://app.example.com/api/auth/magic-link/request`
- `POST https://app.example.com/api/auth/magic-link/exchange`

## 4) Create web routes/pages

Add UI routes for login and exchange landing page in `routes/web.php`:

```php
use App\Http\Controllers\Auth\MagicLinkWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.magic-link-request')->name('login');
    Route::view('/magic-link', 'auth.magic-link-exchange')->name('magic-link.exchange.page');
    Route::post('/login/magic-link/request', [MagicLinkWebController::class, 'request'])->name('web.magic-link.request')
        ->middleware(config('magic-link-auth.request_throttle', 'throttle:5,1'));
    Route::post('/login/magic-link/exchange', [MagicLinkWebController::class, 'exchange'])->name('web.magic-link.exchange')
        ->middleware(config('magic-link-auth.exchange_throttle', 'throttle:10,1'));
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});
```

## 5) Request form (Blade example)

Create `resources/views/auth/magic-link-request.blade.php`:

```blade
<form method="POST" action="{{ route('web.magic-link.request') }}">
    @csrf

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required autocomplete="email" />

    <button type="submit">Send magic link</button>
</form>
```

Notes:

- This form posts to your app route `web.magic-link.request`.
- Your controller calls package service methods directly.
- Keep success UI generic (do not reveal whether user exists).

## 6) Exchange page (Blade + auto-submit example)

Create `resources/views/auth/magic-link-exchange.blade.php`:

```blade
<form id="magic-link-exchange-form" method="POST" action="{{ route('web.magic-link.exchange') }}">
    @csrf
    <input type="hidden" name="email" value="{{ request('email') }}" />
    <input type="hidden" name="token" value="{{ request('token') }}" />
    <noscript>
        <button type="submit">Continue sign in</button>
    </noscript>
</form>

@if (! $errors->any() && request()->filled(['email', 'token']))
<script>
    document.getElementById('magic-link-exchange-form').submit();
</script>
@else
    <p>This magic link is invalid. <a href="{{ route('login') }}">Request a new link</a>.</p>
@endif
```

This page reads `email` and `token` from the clicked link query string and posts them to your web controller, which then uses the package service and redirects.

## 7) Handle exchange response and redirect

Package endpoints return JSON. For a classic web UX, route the request through your own controller, call the package service directly, and redirect.

Example controller approach:

```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Snb4crazy\LaravelMagicLinkAuth\Services\MagicLinkService;

class MagicLinkWebController extends Controller
{
    public function request(Request $request, MagicLinkService $service): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $service->requestMagicLink(
            email: (string) $validated['email'],
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return back()->with('status', 'If that email is registered, a magic link has been sent.');
    }

    public function exchange(Request $request, MagicLinkService $service): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $result = $service->exchangeMagicLink(
            email: (string) $validated['email'],
            plainToken: (string) $validated['token'],
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (($result['status'] ?? null) !== MagicLinkService::STATUS_OK) {
            return redirect()->route('login')->withErrors([
                'email' => config('magic-link-auth.invalid_link_message', 'The magic link is invalid or has expired.'),
            ]);
        }

        return redirect()->route('dashboard');
    }
}
```
An alternative is posting directly to package routes from JS, handling JSON responses client-side, then redirecting.

## 8) Logout and protected pages

Standard Laravel web auth applies:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
```

Protect pages with `auth` middleware as usual.

## 9) Security and production recommendations

- Always use HTTPS in production.
- Keep TTL short (`MAGIC_LINK_TTL_MINUTES=10..20`).
- Keep route throttling enabled (`request_throttle` and `exchange_throttle`).
- Do not log full magic-link URLs (they contain one-time tokens).
- Consider `MAGIC_LINK_REQUIRE_VERIFIED_EMAIL=true` if required by your policy.
- Optionally purge rows from `magic_link_tokens` during account deletion for storage hygiene.

## 10) Common pitfalls

- **Using `['api']` middleware in session mode**: login appears successful but no persistent web session.
- **Missing CSRF token** on POST requests when using `['web']` middleware.
- **Wrong `MAGIC_LINK_FRONTEND_URL`**: user clicks email link but lands on a non-existent route/page.
- **Expecting HTML redirect from package endpoints**: package returns JSON by design.
