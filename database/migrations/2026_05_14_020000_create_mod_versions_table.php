<?php

use App\Models\Mod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mod_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('version');
            $table->string('normalized_version');
            $table->text('changelog');
            $table->string('external_download_url', 2048);
            $table->string('virus_total_url', 2048)->nullable();
            $table->unsignedBigInteger('download_clicks_count')->default(0);
            $table->string('status')->default(Mod::STATUS_PENDING)->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();

            $table->unique(['mod_id', 'normalized_version']);
        });

        Schema::table('security_checks', function (Blueprint $table) {
            $table->foreignId('mod_version_id')->nullable()->after('mod_id')->constrained('mod_versions')->cascadeOnDelete();
        });

        $now = now();

        DB::table('mods')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $mod) use ($now): void {
                DB::table('mod_versions')->insert([
                    'mod_id' => $mod->id,
                    'submitted_by' => $mod->user_id,
                    'version' => '1.0.0',
                    'normalized_version' => '1.0.0.0',
                    'changelog' => 'Initial release.',
                    'external_download_url' => $mod->external_download_url,
                    'virus_total_url' => $mod->virus_total_url,
                    'download_clicks_count' => $mod->download_clicks_count ?? 0,
                    'status' => $mod->status,
                    'rejection_reason' => $mod->rejection_reason,
                    'approved_at' => $mod->approved_at,
                    'reviewed_by' => $mod->reviewed_by,
                    'is_current' => $mod->status === Mod::STATUS_APPROVED,
                    'created_at' => $mod->created_at ?? $now,
                    'updated_at' => $mod->updated_at ?? $now,
                ]);
            });

        DB::table('security_checks')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $securityCheck): void {
                $versionId = DB::table('mod_versions')
                    ->where('mod_id', $securityCheck->mod_id)
                    ->orderByDesc('is_current')
                    ->orderBy('id')
                    ->value('id');

                if ($versionId !== null) {
                    DB::table('security_checks')
                        ->where('id', $securityCheck->id)
                        ->update(['mod_version_id' => $versionId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('security_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mod_version_id');
        });

        Schema::dropIfExists('mod_versions');
    }
};
