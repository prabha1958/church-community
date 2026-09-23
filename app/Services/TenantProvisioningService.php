<?php

namespace App\Services;

use App\Models\Church;
use App\Models\TenantDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantProvisioningService
{
    /**
     * Provision a tenant database for a church.
     *
     * This operation is intentionally NOT wrapped in a database
     * transaction because it involves:
     *
     * 1. Platform database
     * 2. MySQL server-level operations
     * 3. A completely separate tenant database
     */
    public function provision(Church $church): TenantDatabase
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Basic validation
        |--------------------------------------------------------------------------
        */

        $this->validateChurch($church);

        /*
        |--------------------------------------------------------------------------
        | 2. Prevent duplicate provisioning
        |--------------------------------------------------------------------------
        */

        $existingTenant = TenantDatabase::where(
            'church_id',
            $church->id
        )->first();

        if ($existingTenant) {

            if ($existingTenant->status === 'ready') {

                throw new RuntimeException(
                    "Church {$church->church_code} is already provisioned."
                );
            }

            /*
     * A failed provisioning attempt can be retried.
     *
     * Remove the old failed record because the associated
     * database/user have already been cleaned up.
     */
            if ($existingTenant->status === 'failed') {

                $existingTenant->delete();

                $existingTenant = null;
            }

            /*
     * Any other status means another provisioning operation
     * may currently be in progress.
     */
            if ($existingTenant) {

                throw new RuntimeException(
                    "Church {$church->church_code} currently has a tenant "
                        . "provisioning operation with status: "
                        . $existingTenant->status
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Generate tenant credentials
        |--------------------------------------------------------------------------
        */

        $databaseName = $this->generateUniqueDatabaseName($church);

        $databaseUsername = $this->generateUniqueDatabaseUsername($church);

        $databasePassword = $this->generateDatabasePassword();

        $provisioner = DB::connection('provisioner');

        $databaseCreated = false;
        $userCreated = false;

        $tenantDatabase = null;

        try {

            /*
            |--------------------------------------------------------------------------
            | 4. Mark church as provisioning
            |--------------------------------------------------------------------------
            */

            $church->update([
                'status' => 'provisioning',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 5. Create database
            |--------------------------------------------------------------------------
            */

            $this->createDatabase(
                $provisioner,
                $databaseName
            );

            $databaseCreated = true;

            /*
            |--------------------------------------------------------------------------
            | 6. Create MySQL user
            |--------------------------------------------------------------------------
            */

            $this->createDatabaseUser(
                $provisioner,
                $databaseUsername,
                $databasePassword
            );

            $userCreated = true;

            /*
            |--------------------------------------------------------------------------
            | 7. Grant tenant database privileges
            |--------------------------------------------------------------------------
            */

            $this->grantDatabasePrivileges(
                $provisioner,
                $databaseName,
                $databaseUsername
            );

            /*
            |--------------------------------------------------------------------------
            | 8. Save tenant database record
            |--------------------------------------------------------------------------
            */

            $tenantDatabase = TenantDatabase::create([
                'church_id' => $church->id,

                'database_name' => $databaseName,

                'database_username' => $databaseUsername,

                /*
                 * The TenantDatabase model automatically encrypts
                 * this value because of:
                 *
                 * 'database_password' => 'encrypted'
                 */
                'database_password' => $databasePassword,

                'database_host' => config(
                    'database.connections.provisioner.host'
                ),

                'database_port' => config(
                    'database.connections.provisioner.port'
                ),

                'status' => 'creating',

                'error_message' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 9. Configure tenant connection
            |--------------------------------------------------------------------------
            */

            $this->configureTenantConnection(
                $databaseName,
                $databaseUsername,
                $databasePassword
            );

            /*
            |--------------------------------------------------------------------------
            | 10. Verify connection
            |--------------------------------------------------------------------------
            */

            $this->verifyTenantConnection(
                $databaseName
            );

            /*
            |--------------------------------------------------------------------------
            | 11. Run tenant migrations
            |--------------------------------------------------------------------------
            */

            $this->runTenantMigrations();

            /*
            |--------------------------------------------------------------------------
            | 12. Verify migrations
            |--------------------------------------------------------------------------
            */

            $this->verifyTenantMigrations();

            /*
            |--------------------------------------------------------------------------
            | 13. Mark tenant READY
            |--------------------------------------------------------------------------
            */

            $tenantDatabase->update([
                'status' => 'ready',
                'provisioned_at' => now(),
                'error_message' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 14. Activate church
            |--------------------------------------------------------------------------
            */

            $church->update([
                'status' => 'pending',
                'activated_at' => null,
            ]);

            return $tenantDatabase;
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Record failure
            |--------------------------------------------------------------------------
            */

            if ($tenantDatabase) {

                $tenantDatabase->update([
                    'status' => 'failed',
                    'error_message' => Str::limit(
                        $e->getMessage(),
                        5000
                    ),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Return church to pending state
            |--------------------------------------------------------------------------
            */

            $church->update([
                'status' => 'pending',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Cleanup MySQL resources
            |--------------------------------------------------------------------------
            */

            try {

                if ($userCreated) {

                    $this->dropDatabaseUser(
                        $provisioner,
                        $databaseUsername
                    );
                }

                if ($databaseCreated) {

                    $this->dropDatabase(
                        $provisioner,
                        $databaseName
                    );
                }
            } catch (Throwable $cleanupException) {

                /*
                 * Never hide the original provisioning error.
                 */
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    protected function validateChurch(Church $church): void
    {
        if (!$church->exists) {

            throw new RuntimeException(
                'The church record does not exist.'
            );
        }

        if (empty($church->church_code)) {

            throw new RuntimeException(
                'Church code is required before provisioning.'
            );
        }

        /*
         * Only allow letters, numbers and underscore.
         *
         * This prevents invalid MySQL identifiers and avoids
         * dangerous characters entering dynamically generated SQL.
         */
        if (!preg_match('/^[A-Za-z0-9_]+$/', $church->church_code)) {

            throw new RuntimeException(
                'Church code may contain only letters, numbers and underscores.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Generate unique database name
    |--------------------------------------------------------------------------
    */

    protected function generateUniqueDatabaseName(
        Church $church
    ): string {

        $base = 'churchmsg_' .
            strtolower($church->church_code);

        $databaseName = $base;

        $counter = 1;

        while ($this->databaseExists($databaseName)) {

            $databaseName = $base . '_' . $counter;

            $counter++;
        }

        return $databaseName;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate unique MySQL username
    |--------------------------------------------------------------------------
    */

    protected function generateUniqueDatabaseUsername(
        Church $church
    ): string {

        $base = 'cm_' .
            strtolower($church->church_code);

        $username = $base;

        $counter = 1;

        while ($this->mysqlUserExists($username)) {

            $username = $base . '_' . $counter;

            $counter++;
        }

        return $username;
    }


    /*
    |--------------------------------------------------------------------------
    | Secure password
    |--------------------------------------------------------------------------
    */

    protected function generateDatabasePassword(): string
    {
        /*
         * random_bytes gives us cryptographically secure randomness.
         *
         * base64 is converted to URL-safe characters because this
         * password will eventually be used by MySQL.
         */
        return rtrim(
            strtr(
                base64_encode(random_bytes(36)),
                '+/',
                '-_'
            ),
            '='
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check database existence
    |--------------------------------------------------------------------------
    */

    protected function databaseExists(string $databaseName): bool
    {
        $result = DB::connection('provisioner')->select(
            'SELECT SCHEMA_NAME
             FROM INFORMATION_SCHEMA.SCHEMATA
             WHERE SCHEMA_NAME = ?',
            [$databaseName]
        );

        return count($result) > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Check MySQL user existence
    |--------------------------------------------------------------------------
    */

    protected function mysqlUserExists(string $username): bool
    {
        $result = DB::connection('provisioner')->select(
            'SELECT User
             FROM mysql.user
             WHERE User = ?
             AND Host = ?',
            [
                $username,
                'localhost',
            ]
        );

        return count($result) > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Create database
    |--------------------------------------------------------------------------
    */

    protected function createDatabase(
        $connection,
        string $databaseName
    ): void {

        $databaseName = $this->quoteIdentifier(
            $databaseName
        );

        $connection->statement(
            "CREATE DATABASE {$databaseName}
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create MySQL user
    |--------------------------------------------------------------------------
    */

    protected function createDatabaseUser(
        $connection,
        string $username,
        string $password
    ): void {

        $safeUsername = $this->quoteIdentifier(
            $username
        );

        $safePassword = $this->quoteString(
            $password
        );

        $connection->statement(
            "CREATE USER {$safeUsername}@'localhost'
             IDENTIFIED BY {$safePassword}"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Grant privileges
    |--------------------------------------------------------------------------
    */

    protected function grantDatabasePrivileges(
        $connection,
        string $databaseName,
        string $username
    ): void {

        $safeDatabase = $this->quoteIdentifier(
            $databaseName
        );

        $safeUsername = $this->quoteIdentifier(
            $username
        );

        $connection->statement(
            "GRANT ALL PRIVILEGES
             ON {$safeDatabase}.*
             TO {$safeUsername}@'localhost'"
        );

        $connection->statement(
            'FLUSH PRIVILEGES'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Configure dynamic tenant connection
    |--------------------------------------------------------------------------
    */

    protected function configureTenantConnection(
        string $databaseName,
        string $username,
        string $password
    ): void {

        config([
            'database.connections.tenant' => [

                'driver' => 'mysql',

                'host' => config(
                    'database.connections.provisioner.host'
                ),

                'port' => config(
                    'database.connections.provisioner.port'
                ),

                'database' => $databaseName,

                'username' => $username,

                'password' => $password,

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

        DB::purge('tenant');

        DB::reconnect('tenant');
    }


    /*
    |--------------------------------------------------------------------------
    | Verify tenant connection
    |--------------------------------------------------------------------------
    */

    protected function verifyTenantConnection(
        string $expectedDatabase
    ): void {

        $actualDatabase = DB::connection(
            'tenant'
        )->getDatabaseName();

        if ($actualDatabase !== $expectedDatabase) {

            throw new RuntimeException(
                "Tenant connection verification failed. " .
                    "Expected {$expectedDatabase}, " .
                    "got {$actualDatabase}."
            );
        }

        /*
         * Actually execute a query so a bad credential does not
         * go unnoticed.
         */
        DB::connection('tenant')->select(
            'SELECT 1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Run tenant migrations
    |--------------------------------------------------------------------------
    */

    protected function runTenantMigrations(): void
    {
        $exitCode = Artisan::call(
            'migrate',
            [
                '--database' => 'tenant',
                '--path' => 'database/migrations',
                '--force' => true,
            ]
        );

        if ($exitCode !== 0) {

            throw new RuntimeException(
                "Tenant database migrations failed.\n\n" .
                    Artisan::output()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Verify tenant migrations
    |--------------------------------------------------------------------------
    */

    protected function verifyTenantMigrations(): void
    {
        $tables = DB::connection('tenant')
            ->select('SHOW TABLES');

        if (empty($tables)) {

            throw new RuntimeException(
                'Tenant migrations completed but no tables were found.'
            );
        }

        /*
         * The migrations table must exist.
         */
        $migrationTableExists = DB::connection('tenant')
            ->select(
                "SHOW TABLES LIKE 'migrations'"
            );

        if (empty($migrationTableExists)) {

            throw new RuntimeException(
                'Tenant migrations table was not created.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Drop database user
    |--------------------------------------------------------------------------
    */

    protected function dropDatabaseUser(
        $connection,
        string $username
    ): void {

        $safeUsername = $this->quoteIdentifier(
            $username
        );

        $connection->statement(
            "DROP USER IF EXISTS {$safeUsername}@'localhost'"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Drop database
    |--------------------------------------------------------------------------
    */

    protected function dropDatabase(
        $connection,
        string $databaseName
    ): void {

        $safeDatabase = $this->quoteIdentifier(
            $databaseName
        );

        $connection->statement(
            "DROP DATABASE IF EXISTS {$safeDatabase}"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SQL identifier quoting
    |--------------------------------------------------------------------------
    */

    protected function quoteIdentifier(
        string $value
    ): string {

        return '`' .
            str_replace('`', '``', $value) .
            '`';
    }


    /*
    |--------------------------------------------------------------------------
    | SQL string quoting
    |--------------------------------------------------------------------------
    */

    protected function quoteString(
        string $value
    ): string {

        return "'" .
            str_replace(
                ["\\", "'"],
                ["\\\\", "\\'"],
                $value
            ) .
            "'";
    }
}
