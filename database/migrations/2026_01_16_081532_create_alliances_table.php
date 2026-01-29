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
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();

            // owner of this profile (who created it)
            $table->foreignId('member_id')->unique()->constrained('members')->cascadeOnDelete();

            // basic identity
            $table->string('family_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();

            // photos stored as path strings
            $table->string('profile_photo')->nullable();
            $table->string('photo1')->nullable();
            $table->string('photo2')->nullable();
            $table->string('photo3')->nullable();

            // parents
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_occupation')->nullable();

            // education & work
            $table->text('educational_qualifications')->nullable();
            $table->string('profession')->nullable();
            $table->string('designation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('place_of_working')->nullable();

            // descriptive
            $table->text('about_self')->nullable();
            $table->text('about_family')->nullable();

            // Latest payment info (denormalized from alliance_payments)
            $table->integer('amount')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamp('payment_date')->nullable();

            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();

            $table->index(['family_name', 'first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliances');
    }
};
