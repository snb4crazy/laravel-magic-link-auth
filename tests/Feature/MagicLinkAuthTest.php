<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Snb4crazy\LaravelMagicLinkAuth\Mail\MagicLinkMail;
use Snb4crazy\LaravelMagicLinkAuth\Tests\Fixtures\TestUser;
use Snb4crazy\LaravelMagicLinkAuth\Tests\TestCase;

class MagicLinkAuthTest extends TestCase
{
    public function test_request_endpoint_returns_generic_message_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/magic-link/request', [
            'email' => 'missing@example.com',
        ]);

        $response->assertOk()
            ->assertExactJson([
                'message' => 'If that email is registered, a magic link has been sent.',
            ]);

        Mail::assertNothingSent();
    }

    
}
