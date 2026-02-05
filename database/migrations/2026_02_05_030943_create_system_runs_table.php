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
        Schema::create('system_runs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['birthday', 'anniversary']);
            $table->timestamp('last_run_at');
            $table->enum('status', ['success', 'failed']);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_runs');
    }
};
