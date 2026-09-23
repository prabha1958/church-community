<?php

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use App\Services\TenantConnectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformTenant
{
    public function __construct(
        protected TenantConnectionService $tenantConnectionService
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user instanceof PlatformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$user->church) {
            return response()->json([
                'success' => false,
                'message' => 'No church is associated with this account.',
            ], 422);
        }

        try {
            $this->tenantConnectionService->connect(
                $user->church
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to church database.',
                'error' => 'TENANT_CONNECTION_FAILED',
            ], 503);
        }

        $request->attributes->set(
            'church',
            $user->church
        );

        return $next($request);
    }
}
