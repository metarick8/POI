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
        Schema::table('participants_debaters', function (Blueprint $table) {
            $table->unique(['debate_id', 'debater_id'], 'debate_debater_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants_debaters', function (Blueprint $table) {
                        // Drop the foreign keys first (replace with actual constraint names if they differ)
            $table->dropForeign(['debate_id']);
            $table->dropForeign(['debater_id']);

            // Now drop the unique constraint
            $table->dropUnique('debate_debater_unique');
        });
    }
};
