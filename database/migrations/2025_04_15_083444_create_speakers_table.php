<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("team_id");
            $table->enum("position", ['Prime Minister', 'Leader of Opposition', 'Deputy Prime Minister', 'Deputy Leader of Opposition', 'member of Government', 'Member of Opposition', 'Government Whip', 'Opposition Whip']);
            $table->foreign("team_id")->references("id")->on("teams")->onDelete("cascade");
            // $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('speakers');
    }
};
