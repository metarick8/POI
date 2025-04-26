<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debate_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("debate_id");
            $table->unsignedBigInteger("role_id");
            $table->smallInteger("rank");
            $table->foreign("debate_id")->references("id")->on("debates")->onDelete("cascade");
            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debate_results');
    }
};
