<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;

class ChurchLicenseController extends Controller
{
    public function show(Request $request)
    {
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
            ->latest('activation_date')
            ->first();

        if (!$license) {
            return response()->json([
                'success' => true,
                'license' => null,
            ]);
        }

        /*
         * Automatically mark expired licenses.
         */
        if (
            $license->status === 'active' &&
            $license->isExpired()
        ) {
            $license->update([
                'status' => 'expired',
            ]);

            $license->refresh();
        }

        return response()->json([
            'success' => true,

            'church' => [
                'id' => $church->id,
                'church_code' => $church->church_code,
                'church_name' => $church->church_name,
            ],

            'license' => [
                'id' => $license->id,
                'license_key' => $license->license_key,
                'plan' => $license->plan,
                'status' => $license->status,
                'purchase_date' =>
                $license->purchase_date?->toDateString(),
                'activation_date' =>
                $license->activation_date?->toDateString(),
                'expiry_date' =>
                $license->expiry_date?->toDateString(),
                'days_remaining' =>
                $license->isActive()
                    ? $license->daysRemaining()
                    : 0,
                'is_active' =>
                $license->isActive(),
                'is_lifetime' =>
                $license->isLifetime(),
            ],
        ]);
    }
}
