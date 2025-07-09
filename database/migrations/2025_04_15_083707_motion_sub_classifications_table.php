<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motion_sub_classifications', function (Blueprint $table) {
            $table->unsignedBigInteger('motion_id');
            $table->unsignedBigInteger('sub_classification_id');
            $table->primary(['motion_id', 'sub_classification_id']);
            $table->foreign('motion_id')->references('id')->on('motions')->onDelete('cascade');
            $table->foreign('sub_classification_id')->references('id')->on('sub_classifications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motion_sub_classifications');
    }
};
