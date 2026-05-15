<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Mod Collector', 'required_points' => 10, 'color' => '#2563eb', 'icon' => 'archive'],
            ['name' => 'Trusted', 'required_points' => 25, 'color' => '#16a34a', 'icon' => 'shield-check-outline'],
            ['name' => 'Elite Modder', 'required_points' => 50, 'color' => '#7c3aed', 'icon' => 'sparkles'],
            ['name' => 'Mod Master', 'required_points' => 75, 'color' => '#ea580c', 'icon' => 'trophy'],
            ['name' => 'Modding Legend', 'required_points' => 100, 'color' => '#dc2626', 'icon' => 'fire'],
        ])->each(fn (array $rank) => Rank::query()->updateOrCreate(
            ['name' => $rank['name']],
            [
                'required_published_mods' => $rank['required_points'],
                'required_points' => $rank['required_points'],
                'color' => $rank['color'],
                'icon' => $rank['icon'],
                'is_special' => false,
            ],
        ));
    }
}
