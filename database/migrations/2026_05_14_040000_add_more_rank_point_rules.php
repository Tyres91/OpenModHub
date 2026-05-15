<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'key' => 'approved_version',
                'label' => 'Approved new mod version',
                'points' => 10,
                'threshold' => null,
            ],
            [
                'key' => 'rating_received',
                'label' => 'Rating received on approved mod',
                'points' => 2,
                'threshold' => null,
            ],
            [
                'key' => 'rating_average_bonus',
                'label' => 'High average rating bonus',
                'points' => 150,
                'threshold' => 10,
            ],
        ] as $rule) {
            DB::table('rank_point_rules')->updateOrInsert(
                ['key' => $rule['key']],
                [
                    'label' => $rule['label'],
                    'points' => $rule['points'],
                    'threshold' => $rule['threshold'],
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('rank_point_rules')
            ->whereIn('key', ['approved_version', 'rating_received', 'rating_average_bonus'])
            ->delete();
    }
};
