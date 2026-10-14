<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'outstanding_subscriptions',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('member_id')
                    ->constrained('members')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                // Example: 2025-2026
                $table->string('financial_year', 9);

                // Example: ["apr", "may", "jun"]
                $table->json('months_outstanding');

                // All amounts are stored in whole rupees.
                $table->unsignedInteger('original_amount');
                $table->unsignedInteger('amount_paid')->default(0);
                $table->unsignedInteger('balance_amount');

                $table->timestamps();

                $table->unique(
                    ['member_id', 'financial_year'],
                    'outstanding_member_fy_unique'
                );

                $table->index(
                    ['financial_year', 'balance_amount'],
                    'outstanding_fy_balance_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')
            ->dropIfExists('outstanding_subscriptions');
    }
};
