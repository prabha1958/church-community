<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table) {
            $table->id();

            /*
             * Nullable because a super_admin belongs to the
             * application/platform rather than a particular church.
             */
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('name');

            $table->string('email')->unique();

            $table->string('password');

            $table->enum('role', [
                'super_admin',
                'setup_admin',
            ])->default('setup_admin')->index();

            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
            ])->default('active')->index();

            /*
             * Temporary setup administrators will have this set
             * to true initially.
             */
            $table->boolean('must_change_password')
                ->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();

            $table->index([
                'church_id',
                'role',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('platform_users');
    }
};
