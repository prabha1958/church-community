<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Start members AUTO_INCREMENT at 10001
        |--------------------------------------------------------------------------
        |
        | The first member created will receive ID 10001.
        |
        | 10001, 10002, 10003, ...
        |
        */

        DB::connection('tenant')->statement(
            'ALTER TABLE members AUTO_INCREMENT = 50001'
        );


        /*
        |--------------------------------------------------------------------------
        | 2. Create tenant settings table
        |--------------------------------------------------------------------------
        |
        | This table stores settings that belong to the individual
        | church/tenant database.
        |
        */

        Schema::connection('tenant')->create('tenant_settings', function (Blueprint $table) {

            $table->id();

            /*
             * Maximum number of members allowed for this church.
             */
            $table->unsignedInteger('max_members')
                ->default(3000);

            $table->timestamps();
        });


        /*
        |--------------------------------------------------------------------------
        | 3. Insert the default tenant setting
        |--------------------------------------------------------------------------
        */

        DB::connection('tenant')->table('tenant_settings')->insert([
            'max_members' => 3000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove tenant settings
        |--------------------------------------------------------------------------
        */

        Schema::connection('tenant')->dropIfExists(
            'tenant_settings'
        );

        /*
        |--------------------------------------------------------------------------
        | Important:
        |
        | We intentionally do NOT reset AUTO_INCREMENT here.
        |
        | Existing member IDs must never be reused.
        |--------------------------------------------------------------------------
        */
    }
};
