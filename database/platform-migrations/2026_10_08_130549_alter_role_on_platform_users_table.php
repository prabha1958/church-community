<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'owner',
                'setup-admin',
            ])->default('setup-admin')->change();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'setup-admin',
            ])->default('setup-admin')->change();
        });
    }
};
