<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Snb4crazy\LaravelMagicLinkAuth\Mail\MagicLinkMail;

class MagicLinkService
{
    public const STATUS_OK = 'ok';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_VERIFICATION_REQUIRED = 'verification_required';

    public function requestMagicLink(string $email, ?string $ip = null, ?string $userAgent = null): void
    {
        $email = $this->normalizeEmail($email);
        $user = $this->resolveUserByEmail($email);

        if (! $user) {
            return;
        }

        $plainToken = Str::random(64);

        DB::table($this->table())->insert([
            'user_id' => (string) $user->getAuthIdentifier(),
            'email' => $email,
            'token_hash' => Hash::make($plainToken),
            'requested_ip' => $ip,
            'requested_user_agent' => $userAgent,
            'expires_at' => now()->addMinutes($this->ttlMinutes()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($email)->send(new MagicLinkMail(
            magicLinkUrl: $this->buildMagicLinkUrl(email: $email, token: $plainToken),
            appName: config('app.name'),
            expiresInMinutes: $this->ttlMinutes(),
        ));
    }

    /**
     * @return array{status:string, payload?:array{user:mixed, token?:string, token_type?:string, message?:string}}
     */
    public function exchangeMagicLink(string $email, string $plainToken, ?string $ip = null, ?string $userAgent = null): array
    {
        $email = $this->normalizeEmail($email);
        $user = $this->resolveUserByEmail($email);

        if (! $user) {
            return ['status' => self::STATUS_INVALID];
        }

        if ($this->requiresVerifiedEmail() && $this->isEmailUnverified($user)) {
            return ['status' => self::STATUS_VERIFICATION_REQUIRED];
        }

        $rows = DB::table($this->table())
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->limit($this->exchangeCandidateLimit())
            ->get();

        foreach ($rows as $row) {
            if (! Hash::check($plainToken, $row->token_hash)) {
                continue;
            }

            $consumed = DB::table($this->table())
                ->where('id', $row->id)
                ->whereNull('consumed_at')
                ->update([
                    'consumed_at' => now(),
                    'consumed_ip' => $ip,
                    'consumed_user_agent' => $userAgent,
                    'updated_at' => now(),
                ]);

            if ($consumed !== 1) {
                return ['status' => self::STATUS_INVALID];
            }

            return [
                'status' => self::STATUS_OK,
                'payload' => $this->buildAuthPayload($user),
            ];
        }

        return ['status' => self::STATUS_INVALID];
    }

    /**
     * @return array{user:mixed, token?:string, token_type?:string, message?:string}
     */
    private function buildAuthPayload(Authenticatable $user): array
    {
        if ($this->shouldRevokeExistingTokens() && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        if ($this->shouldIssueSanctumToken() && method_exists($user, 'createToken')) {
            $token = $user->createToken((string) config('magic-link-auth.sanctum_token_name', 'magic_link'))->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ];
        }

        Auth::guard((string) config('magic-link-auth.session_guard', 'web'))->login($user);

        return [
            'user' => $user,
            'message' => config('magic-link-auth.session_login_message', 'Logged in successfully.'),
        ];
    }

    private function buildMagicLinkUrl(string $email, string $token): string
    {
        $baseUrl = (string) (config('magic-link-auth.frontend_url') ?: rtrim((string) config('app.url'), '/').'/magic-link');

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query([
            'email' => $email,
            'token' => $token,
        ]);
    }

    private function resolveUserByEmail(string $email): ?Authenticatable
    {
        $userModel = (string) config('magic-link-auth.user_model');
        $emailColumn = (string) config('magic-link-auth.email_column', 'email');

        if (! class_exists($userModel)) {
            return null;
        }

        $user = $userModel::query()->where($emailColumn, $email)->first();

        return $user instanceof Authenticatable ? $user : null;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function table(): string
    {
        return (string) config('magic-link-auth.table', 'magic_link_tokens');
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('magic-link-auth.token_ttl_minutes', 15));
    }

    private function exchangeCandidateLimit(): int
    {
        return max(1, (int) config('magic-link-auth.exchange_candidate_limit', 5));
    }

    private function shouldIssueSanctumToken(): bool
    {
        return (bool) config('magic-link-auth.issue_sanctum_token', true);
    }

    private function shouldRevokeExistingTokens(): bool
    {
        return (bool) config('magic-link-auth.revoke_existing_tokens', false);
    }

    private function requiresVerifiedEmail(): bool
    {
        return (bool) config('magic-link-auth.require_verified_email', false);
    }

    private function isEmailUnverified(Authenticatable $user): bool
    {
        return data_get($user, 'email_verified_at') === null;
    }
}
