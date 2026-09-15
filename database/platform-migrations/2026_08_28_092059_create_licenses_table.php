<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('church_id')
                ->constrained('churches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Example: CMA-7F82-91KD-2026
            $table->string('license_key', 100)->unique();

            $table->enum('plan', [
                'trial',
                'monthly',
                'annual',
                'lifetime',
            ])->default('annual');

            $table->date('purchase_date')->nullable();
            $table->date('activation_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'expired',
                'suspended',
                'cancelled',
            ])->default('pending')->index();

            $table->timestamps();

            $table->index([
                'church_id',
                'status',
            ]);

            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('licenses');
    }
};
