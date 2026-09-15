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
        Schema::table('alliances', function (Blueprint $table) {
            // The unique index is currently being used by the foreign key.
            $table->dropForeign(['member_id']);
            $table->dropUnique('alliances_member_id_unique');

            // Recreate a normal non-unique index.
            $table->index('member_id');

            // Recreate the foreign key without uniqueness.
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alliances', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex(['member_id']);

            // Restore the original one-alliance-per-member constraint.
            $table->unique('member_id');

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });
    }
};
