<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants_debaters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debate_id');
            $table->unsignedBigInteger('debater_id');
            $table->unsignedBigInteger('speaker_id');
            $table->smallInteger("rank");
            $table->foreign('debate_id')->references('id')->on('debates')->onDelete('cascade');
            $table->foreign('debater_id')->references('id')->on('debaters')->onDelete('cascade');
            $table->foreign('speaker_id')->references('id')->on('speakers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants_debaters');
    }
};
