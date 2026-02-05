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
        Schema::create('poor_feedings', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->date('date_of_event'); // event date
            $table->unsignedBigInteger('sponsored_by'); // foreign key to members
            $table->integer('no_of_persons_fed'); // number of persons fed
            $table->json('event_photos')->nullable(); // photos as JSON
            $table->text('brief_description'); // description
            $table->boolean('published')->default(1);
            $table->timestamps(); // created_at, updated_at


            // Foreign key constraint
            $table->foreign('sponsored_by')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poor_feedings');
    }
};
