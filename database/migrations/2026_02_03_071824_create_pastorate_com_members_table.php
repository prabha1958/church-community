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
        Schema::create('pastorate_com_members', function (Blueprint $table) {
            $table->id();

            // Basic details
            $table->string('family_name')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();

            // Period of service
            $table->date('dt_from')->nullable();
            $table->date('dt_to')->nullable();

            // Status: 'in' or 'out'
            $table->enum('status', ['in', 'out'])->default('in')->index();

            // Role/designation (e.g., Secretary, Treasurer, Pastor, Member)
            $table->string('designation')->nullable();

            // Profile photo path
            $table->string('profile_photo')->nullable();

            // Achievements / description
            $table->text('achievements')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastorate_com_members');
    }
};
