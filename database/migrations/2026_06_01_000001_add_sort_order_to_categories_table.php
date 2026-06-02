<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        DB::table('categories')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $category, int $index): void {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['sort_order' => ($index + 1) * 10]);
            });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
