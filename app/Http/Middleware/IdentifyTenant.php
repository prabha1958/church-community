<?php

namespace App\Http\Middleware;

use App\Models\Church;
use App\Services\TenantConnectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        protected TenantConnectionService $tenantConnectionService
    ) {}


    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Get church code
        |--------------------------------------------------------------------------
        |
        | The React Native application sends:
        |
        | X-Church-Code: CWCR
        |
        */

        $churchCode = $request->header('X-Church-Code');

        /*
        |--------------------------------------------------------------------------
        | Church code is required
        |--------------------------------------------------------------------------
        */

        if (!$churchCode) {

            return response()->json([
                'message' => 'Church code is required.',
                'error' => 'TENANT_MISSING',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Find church
        |--------------------------------------------------------------------------
        */

        $church = Church::where(
            'church_code',
            $churchCode
        )->first();

        if (!$church) {

            return response()->json([
                'message' => 'Church not found.',
                'error' => 'TENANT_NOT_FOUND',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Connect to tenant database
        |--------------------------------------------------------------------------
        */

        try {

            $this->tenantConnectionService->connect(
                $church
            );
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Unable to connect to church database.',
                'error' => 'TENANT_CONNECTION_FAILED',
            ], 503);
        }

        /*
        |--------------------------------------------------------------------------
        | Make church available to the request
        |--------------------------------------------------------------------------
        */

        $request->attributes->set(
            'church',
            $church
        );

        /*
        |--------------------------------------------------------------------------
        | Continue request
        |--------------------------------------------------------------------------
        */

        return $next($request);
    }
}
