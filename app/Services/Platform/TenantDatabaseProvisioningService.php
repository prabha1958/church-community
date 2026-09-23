<?php

namespace App\Services\Platform;

use App\Models\TenantDatabase;
use App\Services\TenantConnectionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantDatabaseProvisioningService
{
    /**
     * Create the physical MySQL database and database user
     * represented by a pending TenantDatabase record.
     *
     * State flow:
     *
     * pending
     *    ↓
     * creating
     *
     * This method does NOT run Laravel tenant migrations.
     */
    public function provision(TenantDatabase $tenantDatabase): TenantDatabase
    {
        /*
         * Always work with the platform record.
         */
        $tenantDatabase->setConnection('platform');

        /*
         * Already physically provisioned.
         *
         * Do not recreate anything.
         */
        if (
            $tenantDatabase->status === 'creating'
            || $tenantDatabase->status === 'migrating'
            || $tenantDatabase->status === 'ready'
        ) {
            return $tenantDatabase;
        }

        if ($tenantDatabase->status !== 'pending') {
            throw new RuntimeException(
                "Tenant database #{$tenantDatabase->id} "
                    . "is not in a pending state."
            );
        }

        /*
         * Validate MySQL identifiers before using them
         * in SQL statements.
         */
        $this->validateIdentifier(
            $tenantDatabase->database_name,
            'database name'
        );

        $this->validateIdentifier(
            $tenantDatabase->database_username,
            'database username'
        );

        /*
         * Mark the platform record as creating.
         */
        $tenantDatabase->forceFill([
            'status' => 'creating',
            'error_message' => null,
        ])->save();

        try {

            /*
             * Connect to MySQL using the dedicated provisioning
             * connection.
             */
            $connection = DB::connection('tenant_provisioning');

            /*
             * Verify that the provisioning connection works.
             */
            $connection->getPdo();

            /*
             * Quote the generated identifiers safely.
             */
            $databaseName = $this->quoteIdentifier(
                $tenantDatabase->database_name
            );

            $username = $this->quoteIdentifier(
                $tenantDatabase->database_username
            );

            $usernameString = $connection->getPdo()->quote(
                $tenantDatabase->database_username
            );

            $passwordString = $connection->getPdo()->quote(
                $tenantDatabase->database_password
            );

            /*
             * ---------------------------------------------------------
             * 1. CREATE DATABASE
             * ---------------------------------------------------------
             */
            $connection->statement(
                "CREATE DATABASE IF NOT EXISTS {$databaseName} "
                    . "CHARACTER SET utf8mb4 "
                    . "COLLATE utf8mb4_unicode_ci"
            );

            /*
             * ---------------------------------------------------------
             * 2. CREATE USER
             * ---------------------------------------------------------
             *
             * The host is the same host stored in tenant_databases.
             */
            $userHost = $tenantDatabase->database_host;

            if (!filter_var($userHost, FILTER_VALIDATE_IP)) {
                throw new RuntimeException(
                    "Invalid database host: {$userHost}"
                );
            }

            $userHostString = $connection->getPdo()->quote(
                $userHost
            );

            $connection->statement(
                "CREATE USER IF NOT EXISTS "
                    . "{$username}@{$userHostString} "
                    . "IDENTIFIED BY {$passwordString}"
            );

            /*
             * ---------------------------------------------------------
             * 3. ENSURE PASSWORD IS CORRECT
             * ---------------------------------------------------------
             *
             * This makes the operation recoverable if the MySQL user
             * was created during a previous partial attempt.
             */
            $connection->statement(
                "ALTER USER "
                    . "{$username}@{$userHostString} "
                    . "IDENTIFIED BY {$passwordString}"
            );

            /*
             * ---------------------------------------------------------
             * 4. GRANT TENANT-ONLY PRIVILEGES
             * ---------------------------------------------------------
             */
            $connection->statement(
                "GRANT ALL PRIVILEGES ON "
                    . $databaseName
                    . ".* TO "
                    . "{$username}@{$userHostString}"
            );

            /*
             * ---------------------------------------------------------
             * 5. FLUSH PRIVILEGES
             * ---------------------------------------------------------
             */
            $connection->statement(
                'FLUSH PRIVILEGES'
            );

            /*
             * Physical provisioning has completed.
             *
             * Do NOT mark ready yet.
             *
             * The next stage is:
             *
             * creating → migrating → ready
             */
            $tenantDatabase->forceFill([
                'status' => 'creating',
                'error_message' => null,
            ])->save();

            return $tenantDatabase->fresh();
        } catch (Throwable $e) {

            /*
             * Physical MySQL operations cannot be rolled back by
             * a Laravel platform transaction.
             *
             * Record the failure explicitly.
             */
            $tenantDatabase->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit(
                    $e->getMessage(),
                    5000
                ),
            ])->save();

            throw $e;
        }
    }


    /**
     * Run all tenant Laravel migrations against the newly
     * created physical tenant database.
     *
     * State flow:
     *
     * creating
     *    ↓
     * migrating
     *    ↓
     * ready
     *
     * This method uses a temporary dynamic tenant connection
     * specifically for provisioning.
     */
    public function migrate(TenantDatabase $tenantDatabase): TenantDatabase
    {
        $tenantDatabase->setConnection('platform');

        /*
         * A ready tenant does not need migration again.
         */
        if ($tenantDatabase->status === 'ready') {
            return $tenantDatabase;
        }

        /*
         * Migration should only begin after the physical
         * database has been created.
         */
        if (
            !in_array(
                $tenantDatabase->status,
                ['creating', 'migrating'],
                true
            )
        ) {
            throw new RuntimeException(
                "Tenant database #{$tenantDatabase->id} "
                    . "is not ready for migration. "
                    . "Current status: {$tenantDatabase->status}"
            );
        }

        try {

            /*
             * ---------------------------------------------------------
             * 1. Configure the dynamic tenant connection
             * ---------------------------------------------------------
             *
             * We intentionally do NOT call the normal
             * TenantConnectionService::connect() because that method
             * correctly requires:
             *
             *     Church = active
             *     TenantDatabase = ready
             *
             * Neither condition is true during provisioning.
             */

            config([
                'database.connections.tenant' => [
                    'driver' => 'mysql',

                    'host' =>
                    $tenantDatabase->database_host,

                    'port' =>
                    $tenantDatabase->database_port,

                    'database' =>
                    $tenantDatabase->database_name,

                    'username' =>
                    $tenantDatabase->database_username,

                    'password' =>
                    $tenantDatabase->database_password,

                    'unix_socket' => '',

                    'charset' => 'utf8mb4',

                    'collation' => 'utf8mb4_unicode_ci',

                    'prefix' => '',

                    'prefix_indexes' => true,

                    'strict' => true,

                    'engine' => null,

                    'options' => extension_loaded('pdo_mysql')
                        ? array_filter([
                            \PDO::ATTR_EMULATE_PREPARES => false,
                        ])
                        : [],
                ],
            ]);

            /*
             * Forget any previous tenant connection.
             */
            DB::purge('tenant');

            /*
             * Establish the new connection.
             */
            DB::reconnect('tenant');

            /*
             * Verify that Laravel is connected to the expected
             * tenant database.
             */
            $actualDatabase = DB::connection('tenant')
                ->getDatabaseName();

            if (
                $actualDatabase !==
                $tenantDatabase->database_name
            ) {
                throw new RuntimeException(
                    'Tenant database connection verification failed. '
                        . "Expected {$tenantDatabase->database_name}, "
                        . "got {$actualDatabase}."
                );
            }

            /*
             * ---------------------------------------------------------
             * 2. Mark as migrating
             * ---------------------------------------------------------
             */
            $tenantDatabase->forceFill([
                'status' => 'migrating',
                'error_message' => null,
            ])->save();

            /*
             * ---------------------------------------------------------
             * 3. Run tenant migrations
             * ---------------------------------------------------------
             *
             * These are the existing migrations under:
             *
             *     database/migrations
             *
             * They are NOT the platform migrations.
             */
            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            /*
             * Artisan returning a non-zero exit code means the
             * migration command failed.
             */
            if ($exitCode !== 0) {
                throw new RuntimeException(
                    'Tenant migration command failed. '
                        . 'Artisan output: '
                        . Str::limit(
                            Artisan::output(),
                            5000
                        )
                );
            }

            /*
             * ---------------------------------------------------------
             * 4. Verify migrations table exists
             * ---------------------------------------------------------
             */
            if (
                !DB::connection('tenant')
                    ->getSchemaBuilder()
                    ->hasTable('migrations')
            ) {
                throw new RuntimeException(
                    'Tenant migrations completed without creating '
                        . 'the migrations table.'
                );
            }

            /*
             * ---------------------------------------------------------
             * 5. Mark tenant database READY
             * ---------------------------------------------------------
             */
            $tenantDatabase->forceFill([
                'status' => 'ready',
                'error_message' => null,
                'provisioned_at' => now(),
            ])->save();

            /*
             * Clear the temporary tenant connection.
             */
            DB::purge('tenant');

            return $tenantDatabase->fresh();
        } catch (Throwable $e) {

            /*
             * Migration failure means the physical database exists,
             * but the tenant is not usable yet.
             */
            $tenantDatabase->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit(
                    $e->getMessage(),
                    5000
                ),
            ])->save();

            /*
             * Do not leave a stale connection around.
             */
            DB::purge('tenant');

            throw $e;
        }
    }


    /**
     * Validate a MySQL identifier.
     */
    private function validateIdentifier(
        string $value,
        string $description
    ): void {
        if (
            $value === ''
            || !preg_match('/^[A-Za-z0-9_]+$/', $value)
        ) {
            throw new RuntimeException(
                "Invalid {$description}: {$value}"
            );
        }
    }


    /**
     * Quote a MySQL identifier.
     */
    private function quoteIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }
}
