<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mod_versions', function (Blueprint $table): void {
            $table->string('youtube_preview_url', 2048)->nullable()->after('virus_total_url');
            $table->string('youtube_video_id', 32)->nullable()->after('youtube_preview_url');
        });
    }

    public function down(): void
    {
        Schema::table('mod_versions', function (Blueprint $table): void {
            $table->dropColumn(['youtube_preview_url', 'youtube_video_id']);
        });
    }
};
