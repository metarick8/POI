<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing CHECK constraint
        DB::statement('ALTER TABLE debates DROP CONSTRAINT check_status');

        // Modify the status column to include the new 'teamsConfirmed' status
        DB::statement("ALTER TABLE debates MODIFY COLUMN status VARCHAR(255) NOT NULL
            COMMENT 'Status of the debate (e.g., announced, playersConfirmed, teamsConfirmed, debatePreperation, ongoing, finished, cancelled, bugged)'");

        // Add updated CHECK constraint with new status value
        DB::statement("ALTER TABLE debates ADD CONSTRAINT check_status CHECK
            (status IN ('announced', 'playersConfirmed', 'teamsConfirmed', 'debatePreperation', 'ongoing', 'finished', 'cancelled', 'bugged'))");
    }

    public function down(): void
    {
        // Drop the updated CHECK constraint
        DB::statement('ALTER TABLE debates DROP CONSTRAINT check_status');

        // Revert to the original CHECK constraint without 'teamsConfirmed'
        DB::statement("ALTER TABLE debates MODIFY COLUMN status VARCHAR(255) NOT NULL
            COMMENT 'Status of the debate (e.g., announced, playersConfirmed, debatePreperation, ongoing, finished, cancelled, bugged)'");

        DB::statement("ALTER TABLE debates ADD CONSTRAINT check_status CHECK
            (status IN ('announced', 'playersConfirmed', 'debatePreperation', 'ongoing', 'finished', 'cancelled', 'bugged'))");
    }
};
