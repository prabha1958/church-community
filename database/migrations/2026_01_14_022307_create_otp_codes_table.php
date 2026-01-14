<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpCodesTable extends Migration
{
    public function up()
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable()->index(); // if contact already belongs to member
            $table->string('contact'); // email or mobile (raw)
            $table->string('code_hash'); // hashed OTP
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->string('device_name')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_codes');
    }
}
