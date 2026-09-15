<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->id();

            // Public identifier used by the mobile app / QR code.
            $table->string('church_code', 30)->unique();

            $table->string('church_name');
            $table->string('short_name')->nullable();

            $table->string('email')->nullable();
            $table->string('mobile', 30)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');

            $table->string('logo')->nullable();

            // Example: Asia/Kolkata
            $table->string('timezone', 100)->default('Asia/Kolkata');

            // April = 4
            $table->unsignedTinyInteger('financial_year_start_month')
                ->default(4);

            $table->enum('status', [
                'pending',
                'provisioning',
                'active',
                'suspended',
                'expired',
                'deleted',
            ])->default('pending')->index();

            $table->timestamp('activated_at')->nullable();

            $table->timestamps();

            // Useful for administration/search.
            $table->index('church_name');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('churches');
    }
};
