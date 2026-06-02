<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mods', function (Blueprint $table): void {
            $table->string('external_download_url', 2048)->nullable()->change();
        });

        Schema::table('mod_versions', function (Blueprint $table): void {
            $table->string('external_download_url', 2048)->nullable()->change();
            $table->string('audio_file_path')->nullable()->after('youtube_video_id');
            $table->string('audio_original_name')->nullable()->after('audio_file_path');
            $table->string('audio_mime', 100)->nullable()->after('audio_original_name');
            $table->unsignedBigInteger('audio_size')->nullable()->after('audio_mime');
        });
    }

    public function down(): void
    {
        Schema::table('mod_versions', function (Blueprint $table): void {
            $table->dropColumn(['audio_file_path', 'audio_original_name', 'audio_mime', 'audio_size']);
            $table->string('external_download_url', 2048)->nullable(false)->change();
        });

        Schema::table('mods', function (Blueprint $table): void {
            $table->string('external_download_url', 2048)->nullable(false)->change();
        });
    }
};
