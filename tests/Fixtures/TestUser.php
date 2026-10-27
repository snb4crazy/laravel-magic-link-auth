<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function createToken(string $name): object
    {
        return new class($name)
        {
            public function __construct(
                private readonly string $name,
            ) {}

            public string $plainTextToken = 'test-token-value';

            public function getName(): string
            {
                return $this->name;
            }
        };
    }
}

