<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First make sure every existing value is valid
        // for the new enum.
        DB::connection('platform')
            ->table('platform_users')
            ->update([
                'role' => 'setup-admin',
            ]);

        // Now change the enum.
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'owner',
                'setup-admin',
            ])->default('setup-admin')->change();
        });
    }

    public function down(): void
    {
        // Convert owner accounts back before restoring
        // the original enum.
        DB::connection('platform')
            ->table('platform_users')
            ->where('role', 'owner')
            ->update([
                'role' => 'setup-admin',
            ]);

        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'setup-admin',
            ])->default('setup-admin')->change();
        });
    }
};
