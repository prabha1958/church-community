<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Church;
use App\Services\TenantConnectionService;
use App\Services\AnniversaryGreetingService;

class SendAnniversaryGreetings extends Command
{
    protected $signature = 'greetings:anniversary {--date=}';

    protected $description =
    'Send wedding anniversary greetings to members across all active churches';

    public function handle(
        TenantConnectionService $tenantConnectionService,
        AnniversaryGreetingService $anniversaryGreetingService
    ): int {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        $this->info(
            "Starting anniversary greetings for {$date->toDateString()}..."
        );

        /*
         * Churches are stored in the PLATFORM database.
         */
        $churches = Church::on('platform')
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

        foreach ($churches as $church) {

            $this->newLine();

            $this->info(
                '=================================================='
            );

            $this->info(
                "Processing church: {$church->church_code}"
            );

            $this->info(
                "Church name: {$church->church_name}"
            );

            try {

                /*
                 * Configure the dynamic tenant connection.
                 */
                $tenantConnectionService->connect($church);

                /*
                 * Verify that the tenant connection is available.
                 */
                $databaseName = DB::connection('tenant')
                    ->getDatabaseName();

                $this->info(
                    "Tenant database: {$databaseName}"
                );

                /*
                 * Run anniversary greetings for this church.
                 */
                $anniversaryGreetingService->run(
                    $date,
                    $church,
                    fn($msg) => $this->info($msg)
                );

                $this->info(
                    "✓ Anniversary processing completed for {$church->church_code}"
                );

                $successCount++;
            } catch (\Throwable $e) {

                $failedCount++;

                $this->error(
                    "✗ Failed for {$church->church_code}: {$e->getMessage()}"
                );

                Log::error(
                    'Anniversary greeting failed for church',
                    [
                        'church_id' => $church->id,
                        'church_code' => $church->church_code,
                        'church_name' => $church->church_name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            } finally {

                /*
                 * Disconnect from this tenant before processing
                 * the next church.
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

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            'Anniversary greetings command completed.'
        );

        $this->info(
            "Successful churches: {$successCount}"
        );

        $this->info(
            "Failed churches: {$failedCount}"
        );

        $this->info(
            '=============================================='
        );

        return $failedCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
