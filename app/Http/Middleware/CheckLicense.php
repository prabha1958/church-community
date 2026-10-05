<?php

namespace App\Http\Middleware;

use App\Models\License;
use Closure;
use Illuminate\Http\Request;

class CheckLicense
{
    public function handle(
        Request $request,
        Closure $next
    ) {
        $church = $request->attributes->get('church');

        if (!$church) {
            return response()->json([
                'success' => false,
                'code' => 'CHURCH_CONTEXT_MISSING',
                'message' => 'Church context could not be determined.',
            ], 400);
        }

        $license = License::on('platform')
            ->where('church_id', $church->id)
            ->where('status', 'active')
            ->latest('activation_date')
            ->first();

        /*
         * No license.
         */
        if (!$license) {
            return response()->json([
                'success' => false,
                'code' => 'LICENSE_REQUIRED',
                'message' => 'This church does not have an active license.',
            ], 403);
        }

        /*
         * License expired.
         */
        if ($license->isExpired()) {

            $license->update([
                'status' => 'expired',
            ]);

            return response()->json([
                'success' => false,
                'code' => 'LICENSE_EXPIRED',
                'message' => 'The church license has expired.',
                'license' => [
                    'plan' => $license->plan,
                    'status' => 'expired',
                    'expiry_date' =>
                    $license->expiry_date?->toDateString(),
                ],
            ], 403);
        }

        /*
         * Suspended.
         */
        if ($license->status === 'suspended') {
            return response()->json([
                'success' => false,
                'code' => 'LICENSE_SUSPENDED',
                'message' => 'The church license is suspended.',
            ], 403);
        }

        /*
         * Make license available to controllers.
         */
        $request->attributes->set(
            'license',
            $license
        );

        return $next($request);
    }
}
