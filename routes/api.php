<?php

use Illuminate\Support\Facades\Route;
use Snb4crazy\LaravelMagicLinkAuth\Http\Controllers\MagicLinkAuthController;

Route::prefix(trim(config('magic-link-auth.route_prefix', 'api/auth/magic-link'), '/'))
    ->middleware(config('magic-link-auth.route_middleware', ['api']))
    ->group(function (): void {
        Route::post('/request', [MagicLinkAuthController::class, 'request'])
            ->middleware(config('magic-link-auth.request_throttle', 'throttle:5,1'));

        Route::post('/exchange', [MagicLinkAuthController::class, 'exchange'])
            ->middleware(config('magic-link-auth.exchange_throttle', 'throttle:10,1'));
    });
