<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->string('winner')->nullable()->after('status');
            $table->text('summary')->nullable()->after('winner');
            $table->index('motion_id');
            $table->index('chair_judge_id');
            $table->index('status');
            $table->integer('judge_count')->default(0)->after('status');
            $table->integer('debater_count')->default(0)->after('status');
            // Change ENUM to VARCHAR and add a comment
            DB::statement("ALTER TABLE debates MODIFY COLUMN status VARCHAR(255) NOT NULL
        COMMENT 'Status of the debate (e.g., announced, playersConfirmed, debatePreperation, ongoing, finished, cancelled, bugged)'");
            // Optionally, add a check constraint to restrict values (MySQL 8.0.16+)
            DB::statement("ALTER TABLE debates ADD CONSTRAINT check_status CHECK
        (status IN ('announced', 'playersConfirmed', 'debatePreperation', 'ongoing', 'finished', 'cancelled', 'bugged'))");
        });
    }

    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn(['winner', 'summary']);
            $table->dropIndex(['status']); // Only drop the status index
        });

        // Drop the check constraint
        DB::statement('ALTER TABLE debates DROP CONSTRAINT check_status');

        // Revert to ENUM
        DB::statement("ALTER TABLE debates MODIFY COLUMN status
            ENUM('announced', 'playersConfirmed', 'debatePreperation', 'ongoing', 'finished', 'cancelled', 'bugged') NOT NULL");
    }
};
