<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Snb4crazy\LaravelMagicLinkAuth\Http\Requests\ExchangeMagicLinkRequest;
use Snb4crazy\LaravelMagicLinkAuth\Http\Requests\RequestMagicLinkRequest;
use Snb4crazy\LaravelMagicLinkAuth\Services\MagicLinkService;

class MagicLinkAuthController extends Controller
{
    public function request(RequestMagicLinkRequest $request, MagicLinkService $service): JsonResponse
    {
        $service->requestMagicLink(
            email: (string) $request->input('email'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'message' => config('magic-link-auth.request_response_message'),
        ]);
    }

    public function exchange(ExchangeMagicLinkRequest $request, MagicLinkService $service): JsonResponse
    {
        $result = $service->exchangeMagicLink(
            email: (string) $request->input('email'),
            plainToken: (string) $request->input('token'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if ($result['status'] === MagicLinkService::STATUS_INVALID) {
            return response()->json([
                'message' => config('magic-link-auth.invalid_link_message'),
            ], 422);
        }

        if ($result['status'] === MagicLinkService::STATUS_VERIFICATION_REQUIRED) {
            return response()->json([
                'message' => config('magic-link-auth.verification_required_message'),
                'email_verification_required' => true,
            ], 403);
        }

        /** @var array{user:mixed, token?:string, token_type?:string, message?:string} $payload */
        $payload = $result['payload'] ?? [];

        return response()->json($payload);
    }
}
