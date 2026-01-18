<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alliance_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();

            $table->string('payment_gateway')->nullable();
            $table->string('payment_gateway_order_id')->nullable();
            $table->string('payment_gateway_payment_id')->nullable();
            $table->string('payment_gateway_signature')->nullable();

            $table->integer('amount')->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status')->default('created'); // created, paid, failed
            $table->timestamp('paid_at')->nullable();

            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['alliance_id']);
            $table->index(['member_id']);
            $table->index(['payment_gateway_payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_payments');
    }
};
