<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_sorts', function (Blueprint $table) {
            $table->unsignedBigInteger('resolution_id');
            $table->unsignedBigInteger('sort_id');
            $table->primary(['resolution_id', 'sort_id']);
            $table->foreign('resolution_id')->references('id')->on('resolutions')->onDelete('cascade');
            $table->foreign('sort_id')->references('id')->on('sorts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_sorts');
    }
};
