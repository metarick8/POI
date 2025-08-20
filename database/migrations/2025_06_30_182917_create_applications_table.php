<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('debate_id');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('type')->nullable(); // debater, chair_judge, panelist_judge
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('debate_id')->references('id')->on('debates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
