<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_databases', function (Blueprint $table) {
            $table->id();

            // One tenant database per church.
            $table->foreignId('church_id')
                ->unique()
                ->constrained('churches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('database_name', 100)->unique();
            $table->string('database_username', 100)->unique();

            /*
             * This contains an encrypted value when written through
             * the TenantDatabase model.
             *
             * TEXT is used because encrypted values can be longer
             * than the original password.
             */
            $table->text('database_password');

            $table->string('database_host', 255)->default('127.0.0.1');
            $table->unsignedSmallInteger('database_port')->default(3306);

            $table->enum('status', [
                'pending',
                'creating',
                'migrating',
                'ready',
                'failed',
                'disabled',
            ])->default('pending')->index();

            $table->text('error_message')->nullable();

            $table->timestamp('provisioned_at')->nullable();

            $table->timestamps();

            $table->index([
                'database_host',
                'database_name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('tenant_databases');
    }
};
