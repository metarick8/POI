<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debaters', function (Blueprint $table) {
            $table->id();
            // Check ERD First
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('coach_id')->nullable();
            //$table->bigInteger('points');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coach_id')->references('id')->on('coaches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debaters');
    }
};
