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
        Schema::create('pastors', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Pastor name
            $table->string('designation'); // Designation
            $table->string('qualifications'); // Qualifications
            $table->date('date_of_joining'); // Joining date
            $table->date('date_of_leaving')->nullable(); // Leaving date
            $table->text('past_service_description'); // Past service description
            $table->string('photo')->nullable(); // Photo path
            $table->integer('order_no'); // Ordering number
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastors');
    }
};
