<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->string('meeting_id')->nullable()->after('filter');
            $table->text('start_url')->nullable()->after('meeting_id');
            $table->text('join_url')->nullable()->after('start_url');
            $table->string('password')->nullable()->after('join_url');
        });
    }

    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn(['meeting_id', 'start_url', 'join_url', 'password']);
        });
    }
};
