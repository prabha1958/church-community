<?php

namespace App\Services;

use App\Models\Church;
use App\Models\TenantDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantConnectionService
{
    /**
     * Configure Laravel's dynamic tenant connection
     * for the specified church.
     */
    public function connect(Church $church): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make sure church is active
        |--------------------------------------------------------------------------
        */

        if ($church->status !== 'active') {
            throw new RuntimeException(
                "Church {$church->church_code} is not active."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find tenant database
        |--------------------------------------------------------------------------
        */

        $tenantDatabase = TenantDatabase::where(
            'church_id',
            $church->id
        )
            ->where('status', 'ready')
            ->first();

        if (!$tenantDatabase) {
            throw new RuntimeException(
                "No ready tenant database exists for church {$church->church_code}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Configure dynamic connection
        |--------------------------------------------------------------------------
        |
        | database_password is automatically decrypted by the
        | TenantDatabase model because of the encrypted cast.
        |
        */

        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',

                'host' => $tenantDatabase->database_host,

                'port' => $tenantDatabase->database_port,

                'database' => $tenantDatabase->database_name,

                'username' => $tenantDatabase->database_username,

                'password' => $tenantDatabase->database_password,

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
        |--------------------------------------------------------------------------
        | Forget any previous tenant connection
        |--------------------------------------------------------------------------
        */

        DB::purge('tenant');

        /*
        |--------------------------------------------------------------------------
        | Establish the new connection
        |--------------------------------------------------------------------------
        */

        DB::reconnect('tenant');

        /*
        |--------------------------------------------------------------------------
        | Verify connection
        |--------------------------------------------------------------------------
        */

        $actualDatabase = DB::connection(
            'tenant'
        )->getDatabaseName();

        if ($actualDatabase !== $tenantDatabase->database_name) {

            throw new RuntimeException(
                "Tenant database connection verification failed."
            );
        }
    }


    /**
     * Return the currently configured tenant connection.
     */
    public function connection()
    {
        return DB::connection('tenant');
    }


    /**
     * Return the currently connected tenant database name.
     */
    public function databaseName(): string
    {
        return DB::connection('tenant')
            ->getDatabaseName();
    }
}
