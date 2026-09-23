<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The existing foreign key uses ON DELETE SET NULL.
         * That is incompatible with making church_id NOT NULL.
         *
         * Drop the existing foreign key first.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
        });

        /*
         * Every setup_admin belongs to exactly one church.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->foreignId('church_id')
                ->nullable(false)
                ->change();
        });

        /*
         * Recreate the foreign key without ON DELETE SET NULL.
         *
         * A church cannot be deleted while it has a setup_admin.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->foreign('church_id')
                ->references('id')
                ->on('churches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        /*
         * The platform has only setup_admin accounts.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'setup_admin',
            ])
                ->default('setup_admin')
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * Remove the current foreign key.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
        });

        /*
         * Restore nullable church_id.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->foreignId('church_id')
                ->nullable()
                ->change();
        });

        /*
         * Restore the original SET NULL behavior.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->foreign('church_id')
                ->references('id')
                ->on('churches')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        /*
         * Restore the original role values.
         */
        Schema::connection('platform')->table('platform_users', function (Blueprint $table) {
            $table->enum('role', [
                'super_admin',
                'setup_admin',
            ])
                ->default('setup_admin')
                ->change();
        });
    }
};
