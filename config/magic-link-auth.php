<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('MAGIC_LINK_ROUTE_PREFIX', 'api/auth/magic-link'),
    'route_middleware' => ['api'],
    'request_throttle' => env('MAGIC_LINK_REQUEST_THROTTLE', 'throttle:5,1'),
    'exchange_throttle' => env('MAGIC_LINK_EXCHANGE_THROTTLE', 'throttle:10,1'),

    /*
    |--------------------------------------------------------------------------
    | Token storage
    |--------------------------------------------------------------------------
    */
    'table' => env('MAGIC_LINK_TABLE', 'magic_link_tokens'),
    'token_ttl_minutes' => (int) env('MAGIC_LINK_TTL_MINUTES', 15),
    'exchange_candidate_limit' => (int) env('MAGIC_LINK_EXCHANGE_CANDIDATE_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | User lookup
    |--------------------------------------------------------------------------
    */
    'user_model' => env('MAGIC_LINK_USER_MODEL', config('auth.providers.users.model', \App\Models\User::class)),
    'email_column' => env('MAGIC_LINK_EMAIL_COLUMN', 'email'),
    'require_verified_email' => (bool) env('MAGIC_LINK_REQUIRE_VERIFIED_EMAIL', false),

    /*
    |--------------------------------------------------------------------------
    | Link generation
    |--------------------------------------------------------------------------
    |
    | This URL is included in the email, and query params "email" + "token"
    | are appended to it. For Flutter, this can be a deep-link URL.
    */
    'frontend_url' => env('MAGIC_LINK_FRONTEND_URL'),

    /*
    |--------------------------------------------------------------------------
    | Auth response behavior
    |--------------------------------------------------------------------------
    */
    'issue_sanctum_token' => (bool) env('MAGIC_LINK_ISSUE_SANCTUM_TOKEN', true),
    'sanctum_token_name' => env('MAGIC_LINK_SANCTUM_TOKEN_NAME', 'magic_link'),
    'revoke_existing_tokens' => (bool) env('MAGIC_LINK_REVOKE_EXISTING_TOKENS', false),
    'session_guard' => env('MAGIC_LINK_SESSION_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'request_response_message' => 'If that email is registered, a magic link has been sent.',
    'invalid_link_message' => 'The magic link is invalid or has expired.',
    'verification_required_message' => 'Email verification required before magic-link login.',
    'session_login_message' => 'Logged in successfully.',
];
