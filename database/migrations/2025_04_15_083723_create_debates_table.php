<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('motion_id')->nullable();
            $table->unsignedBigInteger('chair_judge_id')->nullable();
            $table->date('start_date');
            $table->time('start_time')->nullable();
            $table->enum('type', ['onsite', 'online']);
            $table->enum('status', ['announced', 'playersConfirmed', 'debatePreperation','ongoing', 'finished', 'cancelled', 'bugged']);
            $table->string('filter')->nullable();
            $table->foreign('motion_id')->references('id')->on('motions')->onDelete('cascade');
            $table->foreign('chair_judge_id')->references('id')->on('judges')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debates');
    }
};
