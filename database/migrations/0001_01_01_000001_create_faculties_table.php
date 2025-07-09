<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->string('name');
            $table->foreign('university_id')->references('id')->on('universities')->onDelete('cascade');
        });

         DB::table('faculties')->insert([
            ['university_id' => 1, 'name' => 'Faculty of Information Technology Engineering']
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};
