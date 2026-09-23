<?php

namespace App\Http\Middleware;

use App\Models\PlatformPersonalAccessToken;
use App\Models\PlatformUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $accessToken = PlatformPersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired platform token.',
            ], 401);
        }

        if (
            $accessToken->expires_at &&
            $accessToken->expires_at->isPast()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Platform token has expired.',
            ], 401);
        }

        $platformUser = PlatformUser::on('platform')
            ->find($accessToken->tokenable_id);

        if (!$platformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Platform user not found.',
            ], 401);
        }

        if ($platformUser->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Platform user account is not active.',
            ], 403);
        }

        // Update token usage information.
        $accessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        // Make the authenticated platform user available
        // through the request.
        $request->setUserResolver(
            fn() => $platformUser
        );

        return $next($request);
    }
}
