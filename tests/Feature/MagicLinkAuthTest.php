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

    public function test_exchange_requires_verified_email_when_enabled(): void
    {
        config()->set('magic-link-auth.require_verified_email', true);

        Mail::fake();
        TestUser::query()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/auth/magic-link/request', [
            'email' => 'unverified@example.com',
        ])->assertOk();

        $token = $this->firstIssuedToken();

        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'unverified@example.com',
            'token' => $token,
        ])->assertStatus(403)
            ->assertJsonFragment([
                'email_verification_required' => true,
            ]);
    }

    public function test_exchange_returns_api_token_payload_when_enabled(): void
    {
        config()->set('magic-link-auth.issue_sanctum_token', true);
        config()->set('magic-link-auth.sanctum_token_name', 'integration-test');

        Mail::fake();
        TestUser::query()->create([
            'email' => 'api@example.com',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/magic-link/request', [
            'email' => 'api@example.com',
        ])->assertOk();

        $token = $this->firstIssuedToken();

        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'api@example.com',
            'token' => $token,
        ])->assertOk()
            ->assertJsonFragment([
                'token' => 'test-token-value',
                'token_type' => 'Bearer',
            ]);
    }

    private function firstIssuedToken(): string
    {
        Mail::assertSent(MagicLinkMail::class);

        /** @var MagicLinkMail $mail */
        $mail = Mail::sent(MagicLinkMail::class)->last();
        parse_str((string) parse_url((string) $mail->magicLinkUrl, PHP_URL_QUERY), $query);

        return (string) ($query['token'] ?? '');
    }

    public function test_exchange_candidate_limit_config_controls_lookup_window(): void
    {
        config()->set('magic-link-auth.exchange_candidate_limit', 1);

        Mail::fake();
        TestUser::query()->create([
            'email' => 'window@example.com',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/magic-link/request', [
            'email' => 'window@example.com',
        ])->assertOk();
        $firstToken = $this->firstIssuedToken();

        $this->postJson('/api/auth/magic-link/request', [
            'email' => 'window@example.com',
        ])->assertOk();
        $secondToken = $this->firstIssuedToken();

        $this->assertNotSame($firstToken, $secondToken);

        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'window@example.com',
            'token' => $firstToken,
        ])->assertStatus(422);

        $this->postJson('/api/auth/magic-link/exchange', [
            'email' => 'window@example.com',
            'token' => $secondToken,
        ])->assertOk();
    }

    public function test_request_persists_normalized_email(): void
    {
        Mail::fake();
        TestUser::query()->create([
            'email' => 'normalize@example.com',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/magic-link/request', [
            'email' => ' Normalize@Example.Com ',
        ])->assertOk();

        $stored = DB::table(config('magic-link-auth.table'))
            ->latest('id')
            ->first();

        $this->assertNotNull($stored);
        $this->assertSame('normalize@example.com', $stored->email);
    }
}
