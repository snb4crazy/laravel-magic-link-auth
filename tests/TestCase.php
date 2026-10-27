<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Snb4crazy\LaravelMagicLinkAuth\MagicLinkAuthServiceProvider;
use Snb4crazy\LaravelMagicLinkAuth\Tests\Fixtures\TestUser;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MagicLinkAuthServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:6QzUaNchx4Xr2Wf9QPg4qRZOg06ow3D6dj31uR+i8xg=');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('mail.default', 'array');
        $app['config']->set('session.driver', 'array');

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => TestUser::class,
        ]);

        $app['config']->set('magic-link-auth.user_model', TestUser::class);
        $app['config']->set('magic-link-auth.issue_sanctum_token', false);
        $app['config']->set('magic-link-auth.route_middleware', []);
        $app['config']->set('magic-link-auth.request_throttle', []);
        $app['config']->set('magic-link-auth.exchange_throttle', []);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }
}

