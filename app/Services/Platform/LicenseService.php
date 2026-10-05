<?php

namespace App\Services\Platform;

use App\Models\Church;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LicenseService
{
    /**
     * Generate a unique license key.
     */
    public function generateLicenseKey(): string
    {
        do {
            $key =
                'CMA-' .
                strtoupper(Str::random(4)) . '-' .
                strtoupper(Str::random(4)) . '-' .
                now()->format('Y');
        } while (
            License::on('platform')
            ->where('license_key', $key)
            ->exists()
        );

        return $key;
    }

    /**
     * Create a trial license.
     */
    public function createTrial(
        Church $church,
        int $days = 30
    ): License {
        return DB::connection('platform')->transaction(
            function () use ($church, $days) {

                $today = Carbon::today();

                return License::on('platform')->create([
                    'church_id' => $church->id,
                    'license_key' => $this->generateLicenseKey(),
                    'plan' => 'trial',
                    'purchase_date' => $today,
                    'activation_date' => $today,
                    'expiry_date' => $today->copy()->addDays($days),
                    'status' => 'active',
                ]);
            }
        );
    }

    /**
     * Create an annual/monthly/lifetime license.
     */
    public function activate(
        Church $church,
        string $plan
    ): License {
        if (!in_array($plan, [
            'monthly',
            'annual',
            'lifetime',
        ])) {
            throw new RuntimeException(
                "Invalid license plan: {$plan}"
            );
        }

        return DB::connection('platform')->transaction(
            function () use ($church, $plan) {

                $today = Carbon::today();

                /*
                 * Close any currently active license.
                 */
                License::on('platform')
                    ->where('church_id', $church->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                    ]);

                $expiryDate = match ($plan) {
                    'monthly' =>
                    $today->copy()->addMonth(),

                    'annual' =>
                    $today->copy()->addYear(),

                    'lifetime' =>
                    null,
                };

                return License::on('platform')->create([
                    'church_id' => $church->id,
                    'license_key' => $this->generateLicenseKey(),
                    'plan' => $plan,
                    'purchase_date' => $today,
                    'activation_date' => $today,
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                ]);
            }
        );
    }

    /**
     * Renew an existing license.
     *
     * This preserves unused time instead of losing it.
     */
    public function renew(
        License $license,
        string $plan
    ): License {

        if (!in_array($plan, [
            'monthly',
            'annual',
            'lifetime',
        ])) {
            throw new RuntimeException(
                "Invalid license plan: {$plan}"
            );
        }

        return DB::connection('platform')->transaction(
            function () use ($license, $plan) {

                $today = Carbon::today();

                if ($plan === 'lifetime') {

                    $license->update([
                        'plan' => 'lifetime',
                        'purchase_date' => $today,
                        'activation_date' => $today,
                        'expiry_date' => null,
                        'status' => 'active',
                    ]);

                    return $license->fresh();
                }

                /*
                 * If existing license is still valid,
                 * extend from its current expiry date.
                 *
                 * Otherwise start from today.
                 */
                $baseDate =
                    $license->expiry_date &&
                    $license->expiry_date->isFuture()
                    ? $license->expiry_date->copy()
                    : $today->copy();

                $expiryDate = $plan === 'monthly'
                    ? $baseDate->addMonth()
                    : $baseDate->addYear();

                $license->update([
                    'plan' => $plan,
                    'purchase_date' => $today,
                    'activation_date' => $today,
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                ]);

                return $license->fresh();
            }
        );
    }

    /**
     * Get the current license for a church.
     */
    public function current(Church $church): ?License
    {
        return License::on('platform')
            ->where('church_id', $church->id)
            ->whereIn('status', [
                'active',
                'expired',
                'suspended',
            ])
            ->latest('activation_date')
            ->first();
    }

    /**
     * Validate and update expired status.
     */
    public function check(Church $church): ?License
    {
        $license = License::on('platform')
            ->where('church_id', $church->id)
            ->where('status', 'active')
            ->latest('activation_date')
            ->first();

        if (!$license) {
            return null;
        }

        if ($license->isExpired()) {
            $license->update([
                'status' => 'expired',
            ]);

            return $license->fresh();
        }

        return $license;
    }

    public function suspend(License $license): License
    {
        $license->update([
            'status' => 'suspended',
        ]);

        return $license->fresh();
    }

    public function cancel(License $license): License
    {
        $license->update([
            'status' => 'cancelled',
        ]);

        return $license->fresh();
    }
}
