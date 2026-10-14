<?php

namespace App\Console\Commands;

use App\Models\Church;
use App\Models\Member;
use App\Models\OutstandingSubscription;
use App\Models\Subscription;
use App\Models\TenantDatabase;
use App\Services\TenantConnectionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateOutstandingSubscriptions extends Command
{
    protected $signature = 'subscriptions:generate-outstanding
        {--financial-year= : Financial year, e.g. 2025-2026}
        {--church-code= : Process only this church code}';

    protected $description =
    'Save unpaid subscription balances for a closed financial year across tenants';

    public function handle(TenantConnectionService $tenantConnectionService): int
    {
        $financialYear = $this->option('financial-year')
            ?: $this->previousFinancialYear();

        if (
            !preg_match('/^(\d{4})-(\d{4})$/', $financialYear, $matches)
            || (int) $matches[2] !== (int) $matches[1] + 1
        ) {
            $this->error('Invalid financial year. Example: 2025-2026');

            return self::FAILURE;
        }

        $startYear = (int) $matches[1];
        $closingDate = Carbon::create($startYear + 1, 3, 31)->endOfDay();

        $this->info("Processing financial year {$financialYear}");
        $this->info('Closing date: ' . $closingDate->toDateString());

        $tenants = TenantDatabase::query()
            ->where('status', 'ready')
            ->with('church')
            ->when($this->option('church-code'), function ($query) {
                $query->whereHas('church', function ($churchQuery) {
                    $churchQuery->where(
                        'church_code',
                        $this->option('church-code')
                    );
                });
            })
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No ready tenant databases found.');

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        foreach ($tenants as $tenantDatabase) {
            $church = $tenantDatabase->church;

            if (!$church) {
                $this->warn("Tenant database {$tenantDatabase->database_name} has no church.");

                continue;
            }

            try {
                $tenantConnectionService->connect($church);

                $result = DB::connection('tenant')->transaction(
                    function () use ($financialYear, $closingDate) {
                        $existingSubscriptions = Subscription::query()
                            ->where('financial_year', $financialYear)
                            ->get()
                            ->keyBy('member_id');

                        /*
                         * Include currently active members as well as members
                         * who have a subscription record for the closed year.
                         */
                        $memberIdsWithSubscriptions =
                            $existingSubscriptions->keys()->all();

                        $members = Member::query()
                            ->where(function ($query) use ($memberIdsWithSubscriptions) {
                                $query->where('status_flag', true);

                                if (!empty($memberIdsWithSubscriptions)) {
                                    $query->orWhereIn(
                                        'id',
                                        $memberIdsWithSubscriptions
                                    );
                                }
                            })
                            ->get();

                        $created = 0;
                        $skipped = 0;

                        foreach ($members as $member) {
                            /** @var Subscription|null $subscription */
                            $subscription = $existingSubscriptions->get(
                                $member->id
                            );

                            /*
                             * Match the current subscription listing's
                             * behavior for members without a row for this FY.
                             * No existing subscription is created or modified.
                             */
                            if (!$subscription) {
                                $subscription = new Subscription([
                                    'member_id' => $member->id,
                                    'financial_year' => $financialYear,
                                    'monthly_fee' => $member->membership_fee ?? 0,
                                ]);
                            }

                            $monthlyFee = (int) $subscription->monthly_fee;

                            if ($monthlyFee <= 0) {
                                $skipped++;
                                continue;
                            }

                            $unpaidMonths = $subscription->unpaidMonthsUpTo(
                                $closingDate
                            );

                            $amount = count($unpaidMonths) * $monthlyFee;

                            if ($amount <= 0) {
                                $skipped++;
                                continue;
                            }

                            /*
                             * Idempotent: the unique member/year key and
                             * firstOrCreate prevent reruns from resetting
                             * balances or erasing collection history.
                             */
                            $outstanding = OutstandingSubscription::firstOrCreate(
                                [
                                    'member_id' => $member->id,
                                    'financial_year' => $financialYear,
                                ],
                                [
                                    'months_outstanding' => array_values($unpaidMonths),
                                    'original_amount' => $amount,
                                    'amount_paid' => 0,
                                    'balance_amount' => $amount,
                                ]
                            );

                            if ($outstanding->wasRecentlyCreated) {
                                $created++;
                            } else {
                                $skipped++;
                            }
                        }

                        return compact('created', 'skipped');
                    }
                );

                $processed++;

                $this->info(
                    "{$church->church_code}: created {$result['created']}, "
                        . "skipped {$result['skipped']}"
                );
            } catch (Throwable $e) {
                $failed++;

                Log::error('Year-end outstanding generation failed', [
                    'church_code' => $church->church_code,
                    'database_name' => $tenantDatabase->database_name,
                    'financial_year' => $financialYear,
                    'error' => $e->getMessage(),
                ]);

                $this->error(
                    "{$church->church_code}: failed — {$e->getMessage()}"
                );
            } finally {
                // Do not leave one tenant's connection selected for the next.
                DB::purge('tenant');
            }
        }

        $this->info("Completed. Tenants processed: {$processed}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function previousFinancialYear(): string
    {
        $today = Carbon::today();

        $currentFinancialYearStart = $today->month >= 4
            ? $today->year
            : $today->year - 1;

        $previousStart = $currentFinancialYearStart - 1;

        return $previousStart . '-' . ($previousStart + 1);
    }
}
