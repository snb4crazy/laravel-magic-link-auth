<?php

namespace Snb4crazy\LaravelMagicLinkAuth;

use Illuminate\Support\ServiceProvider;
use Snb4crazy\LaravelMagicLinkAuth\Services\MagicLinkService;

class MagicLinkAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/magic-link-auth.php', 'magic-link-auth');

        $this->app->singleton(MagicLinkService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'magic-link-auth');

        $this->publishes([
            __DIR__.'/../config/magic-link-auth.php' => config_path('magic-link-auth.php'),
        ], 'magic-link-auth-config');
    }
}
