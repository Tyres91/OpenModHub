<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $users = DB::table('users')
                ->where('name', $duplicate->name)
                ->orderBy('id')
                ->get();

            $counter = 2;
            foreach ($users->skip(1) as $user) {
                $newName = $user->name . '_' . $counter;
                while (DB::table('users')->where('name', $newName)->exists()) {
                    $counter++;
                    $newName = $user->name . '_' . $counter;
                }
                DB::table('users')->where('id', $user->id)->update(['name' => $newName]);
                $counter++;
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });
    }
};
