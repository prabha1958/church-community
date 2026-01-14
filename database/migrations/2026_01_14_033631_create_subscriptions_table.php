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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // Financial year stored as '2025-2026' or '2025' representing FY starting year
            $table->string('financial_year')->index();

            // monthly fee (store in integer rupees here; recommend storing in paisa in real system)
            $table->integer('monthly_fee')->default(100); // ₹100/month default — change as needed

            // APR to MAR columns: payment id and paid_at
            $months = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];
            foreach ($months as $m) {
                $table->string("{$m}_payment_id")->nullable()->index();
                $table->timestamp("{$m}_paid_at")->nullable();
            }

            $table->timestamps();
            $table->unique(['member_id', 'financial_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
