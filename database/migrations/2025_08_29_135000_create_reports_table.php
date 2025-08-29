<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debate_id');
            $table->unsignedBigInteger('reporter_id'); // User ID of the reporter
            $table->string('reporter_type'); // 'debater', 'judge'
            $table->string('issue_type'); // 'technical', 'behavioral', 'procedural', 'other'
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'under_review', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->string('admin_action')->nullable(); // 'none', 'warning', 'ban', 'comment'
            $table->unsignedBigInteger('admin_id')->nullable(); // Admin who handled the report
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('debate_id')->references('id')->on('debates')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            
            $table->index(['debate_id', 'reporter_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};