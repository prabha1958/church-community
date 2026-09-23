<?php

namespace App\Services\Platform;

use App\Models\Church;
use App\Models\PlatformUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use App\Models\TenantDatabase;


class PlatformOnboardingService
{
    /**
     * Maximum number of attempts when generating a unique
     * church code / tenant identity.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Register a new church onboarding request.
     *
     * At this stage we only validate the email and generate
     * a tenant identity. No Church record or tenant database
     * is created here.
     *
     * @return array<string, mixed>
     */
    public function register(string $email): array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            throw new RuntimeException('Email address is required.');
        }

        /*
         * A platform account must be unique by email.
         *
         * This check is application-level because the current
         * platform_users table does not have a global unique
         * constraint on email.
         */
        $existingUser = PlatformUser::on('platform')
            ->where('email', $email)
            ->first();

        if ($existingUser) {
            throw new RuntimeException(
                'A platform account already exists for this email.'
            );
        }

        /*
         * Generate the church code and tenant database identity.
         *
         * This does not create anything in the database yet.
         */
        $tenantIdentity = $this->generateTenantIdentity();

        return [
            'email' => $email,
            'status' => 'pending',
            'tenant_identity' => $tenantIdentity,
        ];
    }

    /**
     * Generate a unique tenant identity.
     *
     * The church code is protected by the database-level
     * unique constraint on churches.church_code.
     *
     * The preliminary exists() check reduces the chance of
     * hitting that constraint, while the actual Church insert
     * in reserveChurch() remains the final protection.
     *
     * @return array<string, string>
     */
    public function generateTenantIdentity(): array
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {

            /*
             * Generate an 8-character random identifier.
             *
             * Example:
             *   7f42a91b
             */
            $identifier = strtolower(Str::random(8));

            /*
             * Public church code.
             *
             * Example:
             *   CHR7F42A91B
             */
            $churchCode = 'CHR' . strtoupper($identifier);

            /*
             * Tenant database identity.
             *
             * Example:
             *   churchmsg_7f42a91b
             *   tenant_7f42a91b
             */
            $databaseName = 'churchmsg_' . $identifier;
            $databaseUsername = 'cm_' . $identifier;

            /*
             * Preliminary check against the platform database.
             *
             * The unique database constraint on churches.church_code
             * is still the final authority.
             */
            $churchExists = Church::on('platform')
                ->where('church_code', $churchCode)
                ->exists();

            if ($churchExists) {
                continue;
            }

            return [
                'church_code' => $churchCode,
                'database_name' => $databaseName,
                'database_username' => $databaseUsername,
                'database_password' => Str::random(32),
            ];
        }

        throw new RuntimeException(
            'Unable to generate a unique church code after '
                . self::MAX_ATTEMPTS
                . ' attempts.'
        );
    }
    /**
     * Reserve a Church and its tenant database configuration
     * in a single platform database transaction.
     *
     * This operation:
     *   - creates the platform Church record
     *   - creates the platform TenantDatabase record
     *   - does NOT create the actual MySQL tenant database
     *   - does NOT create the MySQL database user
     *   - does NOT run tenant migrations
     *   - does NOT create the setup admin
     *
     * @return array<string, mixed>
     */
    public function reserveChurch(string $email): array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            throw new RuntimeException('Email address is required.');
        }

        /*
     * Do not create another onboarding account for an email
     * that already belongs to a platform user.
     */
        $existingUser = PlatformUser::on('platform')
            ->where('email', $email)
            ->first();

        if ($existingUser) {
            throw new RuntimeException(
                'A platform account already exists for this email.'
            );
        }

        /*
     * Try several times in case of the extremely unlikely
     * event of a church_code collision.
     *
     * The database UNIQUE constraint on churches.church_code
     * remains the final protection.
     */
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {

            $identity = $this->generateTenantIdentity();

            try {

                /*
             * Both platform records are created in the SAME
             * platform database transaction.
             *
             * If either insert fails, both are rolled back.
             */
                $result = DB::connection('platform')->transaction(
                    function () use ($identity, $email) {

                        /*
                     * Create the Church reservation.
                     *
                     * Required values from the current schema:
                     *
                     * church_code
                     * church_name
                     * country
                     * timezone
                     * financial_year_start_month
                     * status
                     */
                        $church = Church::on('platform')->create([
                            'church_code' => $identity['church_code'],

                            /*
                         * Temporary name.
                         *
                         * The setup admin will replace this with
                         * the actual church name later.
                         */
                            'church_name' =>
                            'New Church - ' . $identity['church_code'],

                            'short_name' => null,
                            'email' => $email,
                            'mobile' => null,
                            'address' => null,
                            'city' => null,
                            'state' => null,
                            'logo' => null,

                            /*
                         * These are NOT NULL in the current schema.
                         */
                            'country' => 'India',
                            'timezone' => 'Asia/Kolkata',
                            'financial_year_start_month' => 4,

                            /*
                         * New churches begin in the pending state.
                         */
                            'status' => 'pending',
                        ]);

                        /*
                     * Create the platform-side tenant database
                     * configuration.
                     *
                     * This does NOT create the actual MySQL database.
                     */
                        $tenantDatabase = \App\Models\TenantDatabase::on('platform')
                            ->create([
                                'church_id' => $church->id,

                                'database_name' =>
                                $identity['database_name'],

                                'database_username' =>
                                $identity['database_username'],

                                'database_password' =>
                                $identity['database_password'],

                                'database_host' => '127.0.0.1',

                                'database_port' => 3306,

                                /*
                             * Physical database has not been created yet.
                             */
                                'status' => 'pending',

                                'error_message' => null,

                                'provisioned_at' => null,
                            ]);

                        return [
                            'church' => $church,
                            'tenant_database' => $tenantDatabase,
                        ];
                    }
                );

                return [
                    'church' => $result['church'],
                    'tenant_database' => $result['tenant_database'],
                ];
            } catch (QueryException $e) {

                /*
             * churches.church_code has a database-level UNIQUE
             * constraint.
             *
             * If a concurrent request generated the same code,
             * retry with a new identity.
             */
                if (
                    $e->getCode() === '23000'
                    && str_contains(
                        strtolower($e->getMessage()),
                        'duplicate'
                    )
                ) {
                    continue;
                }

                /*
             * Do not silently retry unrelated database errors.
             */
                throw $e;
            }
        }

        throw new RuntimeException(
            'Unable to reserve a unique church code after '
                . self::MAX_ATTEMPTS
                . ' attempts.'
        );
    }

    public function complete(
        Church $church,
        PlatformUser $user
    ): Church {
        return DB::connection('platform')->transaction(
            function () use ($church, $user) {

                /*
             * Lock the church row so two completion requests
             * cannot activate it simultaneously.
             */
                $church = Church::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($church->id);

                /*
             * The authenticated setup admin must belong
             * to this church.
             */
                if ((int) $user->church_id !== (int) $church->id) {
                    throw new RuntimeException(
                        'You are not authorized to complete onboarding for this church.'
                    );
                }

                /*
             * Only an active setup administrator may
             * complete onboarding.
             */
                if (
                    $user->role !== 'setup_admin' ||
                    $user->status !== 'active'
                ) {
                    throw new RuntimeException(
                        'Only an active setup administrator can complete onboarding.'
                    );
                }

                /*
             * The temporary password must already have
             * been changed.
             */
                if ($user->must_change_password) {
                    throw new RuntimeException(
                        'The setup administrator must change the temporary password before completing onboarding.'
                    );
                }

                /*
             * Only pending/provisioning churches can be
             * completed.
             */
                if (!in_array(
                    $church->status,
                    ['pending', 'provisioning'],
                    true
                )) {
                    throw new RuntimeException(
                        "Church {$church->church_code} is not in an onboarding state."
                    );
                }

                /*
             * Tenant database must be ready.
             */
                $tenantDatabase = TenantDatabase::on('platform')
                    ->where('church_id', $church->id)
                    ->lockForUpdate()
                    ->first();

                if (!$tenantDatabase) {
                    throw new RuntimeException(
                        'No tenant database is configured for this church.'
                    );
                }

                if ($tenantDatabase->status !== 'ready') {
                    throw new RuntimeException(
                        "The tenant database is not ready. Current status: {$tenantDatabase->status}."
                    );
                }

                /*
             * Church name is required.
             */
                if (trim((string) $church->church_name) === '') {
                    throw new RuntimeException(
                        'Church name is required before completing onboarding.'
                    );
                }

                /*
             * All prerequisites passed.
             *
             * This is the controlled activation point.
             */
                $church->forceFill([
                    'status' => 'active',
                    'activated_at' => now(),
                ])->save();

                return $church->fresh();
            }
        );
    }
}
