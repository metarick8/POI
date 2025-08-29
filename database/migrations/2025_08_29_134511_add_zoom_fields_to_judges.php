<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judges', function (Blueprint $table) {  // Assuming 'judges' table exists
            $table->string('zoom_id')->nullable();
            $table->string('zoom_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropColumn(['zoom_id', 'zoom_email']);
        });
    }
};
