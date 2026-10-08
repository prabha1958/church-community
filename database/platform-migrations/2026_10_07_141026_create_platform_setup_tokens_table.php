<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('platform')->create(
            'platform_setup_tokens',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('church_id');

                $table->unsignedBigInteger(
                    'registration_request_id'
                )->nullable();

                $table->string('email');

                $table->string('token_hash', 64)->unique();

                $table->timestamp('expires_at');

                $table->timestamp('used_at')->nullable();

                $table->timestamps();

                $table->index('church_id');
                $table->index('registration_request_id');
                $table->index('expires_at');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('platform_setup_tokens');
    }
};
