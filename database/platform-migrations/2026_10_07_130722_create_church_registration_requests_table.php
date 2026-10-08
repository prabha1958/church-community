<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('platform')->create(
            'church_registration_requests',
            function (Blueprint $table) {
                $table->id();

                $table->string('church_name', 255);
                $table->text('address');
                $table->string('city', 100);

                $table->string('admin_name', 255);
                $table->string('admin_email', 255);

                $table->enum('status', [
                    'pending',
                    'approved',
                    'rejected',
                ])->default('pending')->index();

                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();

                $table->unsignedBigInteger('church_id')->nullable();

                $table->text('admin_notes')->nullable();

                $table->timestamps();

                $table->index([
                    'admin_email',
                    'status',
                ]);

                $table->index([
                    'church_name',
                    'city',
                    'status',
                ]);

                $table->index('church_id');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('church_registration_requests');
    }
};
