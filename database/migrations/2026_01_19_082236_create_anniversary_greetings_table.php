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
        Schema::create('anniversary_greetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->date('wedding_date'); // original wedding date
            $table->date('sent_on'); // date when greeting was sent
            $table->string('channel')->nullable(); // e.g. 'whatsapp', 'sms', 'email'
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique(['member_id', 'sent_on']); // prevent duplicates same day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anniversary_greetings');
    }
};
