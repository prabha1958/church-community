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
        Schema::create('members', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK, bigint
            $table->string('family_name'); // required
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('wedding_date')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('gender')->nullable();
            $table->boolean('status_flag')->default(true);
            $table->string('email')->unique()->nullable();
            $table->string('mobile_number')->unique()->nullable();
            $table->string('occupation')->nullable();
            $table->enum('status', ['in_service', 'retired', 'other'])->default('in_service');
            $table->string('profile_photo')->nullable(); // path to file
            $table->string('couple_pic')->nullable();
            $table->string('role')->default('member');
            $table->string('area_no');
            $table->integer('membership_fee')->nullable();
            $table->string('address_flat_number')->nullable();
            $table->string('address_premises')->nullable();
            $table->string('address_area')->nullable();
            $table->string('address_landmark')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_pin')->nullable();
            $table->date('last_birthday_greeted_at')->nullable();
            $table->date('last_anniversary_greeted_at')->nullable();
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
