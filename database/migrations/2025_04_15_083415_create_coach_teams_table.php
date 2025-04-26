<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debater_id');
            $table->unsignedBigInteger('coach_id');
            $table->date("start_date");
            $table->date("end_date");
            $table->foreign('debater_id')->references('id')->on('debaters')->onDelete('cascade');
            $table->foreign('coach_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_teams');
    }
};
