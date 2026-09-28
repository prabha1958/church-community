<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Church;
use App\Services\TenantConnectionService;
use App\Services\BirthdayGreetingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendBirthdayWishes extends Command
{
    protected $signature = 'send:birthday-wishes
        {--whatsapp : send WhatsApp messages instead of SMS}
        {--dry : dry run - do not actually send messages}
        {--template= : custom message template (optional)}';

    protected $description =
    'Send birthday wishes to members across all active churches';

    public function handle(
        TenantConnectionService $tenantConnectionService,
        BirthdayGreetingService $birthdayGreetingService
    ): int {

        $this->info('Starting birthday wishes command...');

        /*
        |--------------------------------------------------------------------------
        | Get active churches from PLATFORM database
        |--------------------------------------------------------------------------
        */

        $churches = Church::query()
            ->where('status', 'active')
            ->get();

        if ($churches->isEmpty()) {
            $this->warn('No active churches found.');
            return self::SUCCESS;
        }

        $this->info(
            "Found {$churches->count()} active church(es)."
        );

        $successCount = 0;
        $failedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Process each church
        |--------------------------------------------------------------------------
        */

        foreach ($churches as $church) {

            $this->newLine();

            $this->info(
                "=================================================="
            );

            $this->info(
                "Processing church: {$church->church_code}"
            );

            $this->info(
                "Church name: {$church->church_name}"
            );

            try {

                /*
                |--------------------------------------------------------------------------
                | Connect to this church's tenant database
                |--------------------------------------------------------------------------
                */

                $tenantConnectionService->connect($church);

                $databaseName = DB::connection('tenant')
                    ->getDatabaseName();

                $this->info(
                    "Tenant database: {$databaseName}"
                );

                /*
                |--------------------------------------------------------------------------
                | Run birthday processing for this tenant
                |--------------------------------------------------------------------------
                */

                $birthdayGreetingService->run(
                    $this->option('whatsapp')
                );

                $this->info(
                    "✓ Birthday processing completed for {$church->church_code}"
                );

                $successCount++;
            } catch (\Throwable $e) {

                $failedCount++;

                $this->error(
                    "✗ Failed for {$church->church_code}: {$e->getMessage()}"
                );

                Log::error(
                    'Birthday greeting failed for church',
                    [
                        'church_id' => $church->id,
                        'church_code' => $church->church_code,
                        'church_name' => $church->church_name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Important:
                | Continue with the next church.
                |--------------------------------------------------------------------------
                */

                continue;
            } finally {

                /*
                |--------------------------------------------------------------------------
                | Disconnect tenant after processing this church.
                |--------------------------------------------------------------------------
                */

                try {
                    DB::purge('tenant');
                } catch (\Throwable $e) {
                    Log::warning(
                        'Unable to purge tenant connection',
                        [
                            'church_code' => $church->church_code,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('==============================================');
        $this->info('Birthday wishes command completed.');
        $this->info("Successful churches: {$successCount}");
        $this->info("Failed churches: {$failedCount}");
        $this->info('==============================================');

        return $failedCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
