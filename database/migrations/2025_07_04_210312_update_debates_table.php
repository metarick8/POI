<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        });
    }

    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn(['winner', 'summary', 'cancellation_reason']);
            $table->dropIndex(['motion_id']);
            $table->dropIndex(['chair_judge_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['judge_count']);
            $table->dropIndex(['debater_count']);
        });
    }
};
