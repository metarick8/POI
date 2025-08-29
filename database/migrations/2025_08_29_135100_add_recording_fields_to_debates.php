<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->string('recording_type')->nullable()->after('password'); // 'zoom_link', 'cloudinary_upload'
            $table->text('zoom_recording_url')->nullable()->after('recording_type');
            $table->string('cloudinary_recording_id')->nullable()->after('zoom_recording_url');
            $table->string('cloudinary_recording_url')->nullable()->after('cloudinary_recording_id');
            $table->timestamp('recording_uploaded_at')->nullable()->after('cloudinary_recording_url');
            $table->text('final_ranks')->nullable()->after('recording_uploaded_at'); // JSON field for team rankings
        });
    }

    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn([
                'recording_type',
                'zoom_recording_url', 
                'cloudinary_recording_id',
                'cloudinary_recording_url',
                'recording_uploaded_at',
                'final_ranks'
            ]);
        });
    }
};