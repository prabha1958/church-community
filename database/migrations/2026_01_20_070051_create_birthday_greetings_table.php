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

        Schema::create('birthday_greetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->date('greeted_on');           // actual date sent
            $table->year('greeted_year');         // for yearly uniqueness
            $table->boolean('email_sent')->default(false);
            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamps();

            $table->unique(['member_id', 'greeted_year']);
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birthday_greetings');
    }
};
