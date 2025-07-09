<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panelist_judges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("debate_id");
            $table->unsignedBigInteger("judge_id");
            $table->foreign("debate_id")->references("id")->on("debates")->onDelete("cascade");
            $table->foreign("judge_id")->references("id")->on("judges")->onDelete("cascade");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panelist_judges');
    }
};
