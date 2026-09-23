<?php

namespace App\Services\Platform;

use App\Models\Church;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Str;

class ChurchRegistrationService
{
    public function __construct(
        private TenantProvisioningService $tenantProvisioningService
    ) {}

    /**
     * Register a church and provision its tenant database.
     *
     * The tenant provisioning service owns the provisioning
     * lifecycle and cleanup. We deliberately do not wrap this
     * operation in a platform DB transaction because provisioning
     * creates MySQL databases/users outside the platform database.
     */
    public function register(array $data): Church
    {
        $churchCode = $this->generateChurchCode();

        /*
         * Create the platform church first.
         *
         * It starts as pending.
         */
        $church = Church::on('platform')->create([
            'church_code' => $churchCode,

            'church_name' => trim($data['church_name']),

            'short_name' => isset($data['short_name'])
                ? trim($data['short_name'])
                : null,

            'email' => !empty($data['email'])
                ? strtolower(trim($data['email']))
                : null,

            'mobile' => !empty($data['mobile'])
                ? trim($data['mobile'])
                : null,

            'address' => !empty($data['address'])
                ? trim($data['address'])
                : null,

            'city' => !empty($data['city'])
                ? trim($data['city'])
                : null,

            'state' => !empty($data['state'])
                ? trim($data['state'])
                : null,

            'country' => !empty($data['country'])
                ? trim($data['country'])
                : 'India',

            'timezone' => !empty($data['timezone'])
                ? trim($data['timezone'])
                : 'Asia/Kolkata',

            'financial_year_start_month' =>
            isset($data['financial_year_start_month'])
                ? (int) $data['financial_year_start_month']
                : 4,

            'status' => 'pending',

            'activated_at' => null,
        ]);

        /*
         * This creates:
         *
         * - tenant database
         * - tenant MySQL user
         * - privileges
         * - tenant connection
         * - tenant migrations
         * - tenant database record
         *
         * It also performs its own failure cleanup.
         */
        $this->tenantProvisioningService->provision($church);

        /*
         * Refresh so the caller receives the current platform state.
         */
        $church->refresh();

        return $church;
    }

    private function generateChurchCode(): string
    {
        do {
            $code = 'CHR' . strtoupper(
                Str::random(8)
            );
        } while (
            Church::on('platform')
            ->where('church_code', $code)
            ->exists()
        );

        return $code;
    }
}
