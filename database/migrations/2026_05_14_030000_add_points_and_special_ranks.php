<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->unsignedInteger('required_points')->default(0)->after('required_published_mods')->index();
            $table->boolean('is_special')->default(false)->after('icon')->index();
        });

        DB::table('ranks')->update([
            'required_points' => DB::raw('required_published_mods'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rank_id')->nullable()->after('locale')->constrained('ranks')->nullOnDelete();
        });

        Schema::create('rank_point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->integer('points')->default(0);
            $table->unsignedInteger('threshold')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('rank_point_rules')->insert([
            [
                'key' => 'comment_created',
                'label' => 'Visible comment created',
                'points' => 5,
                'threshold' => null,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'approved_mod',
                'label' => 'Approved mod upload',
                'points' => 20,
                'threshold' => null,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'download_threshold',
                'label' => 'Mod download threshold reached',
                'points' => 100,
                'threshold' => 1000,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_point_rules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rank_id');
        });

        Schema::table('ranks', function (Blueprint $table) {
            $table->dropColumn(['required_points', 'is_special']);
        });
    }
};
