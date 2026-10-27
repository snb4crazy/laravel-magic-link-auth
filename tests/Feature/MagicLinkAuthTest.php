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

    public function test_exchange_consumes_token_once_with_session_mode(): void
    {
        Mail::fake();
        TestUser::query()->create([
            'email' => 'mixedcase@example.com',
            'email_verified_at' => now(),
        ]);
        
        $this->postJson('/api/auth/magic-link/request', [
            'email' => 'MiXeDCaSe@example.com',
        ])->assertOk();
        
        $token = $this->firstIssuedToken();
        
        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'MIXEDCASE@example.com',
            'token' => $token,
        ])->assertOk()
            ->assertJsonFragment([
                'message' => 'Logged in successfully.',
            ]);
        
        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'mixedcase@example.com',
            'token' => $token,
        ])->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'The magic link is invalid or has expired.',
            ]);
    }
    
    
}
