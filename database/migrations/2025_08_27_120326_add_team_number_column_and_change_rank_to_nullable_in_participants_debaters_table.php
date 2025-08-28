<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants_debaters', function (Blueprint $table) {
            $table->smallInteger("rank")->nullable()->change();
            $table->smallInteger('team_number')->after('debater_id');
        });
    }

    public function down(): void
    {
        Schema::table('participants_debaters', function (Blueprint $table) {
            $table->dropColumn('team_number');
        });
    }
};
