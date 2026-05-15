<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed initial mod categories.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Gameplay', 'slug' => 'gameplay', 'description' => 'Mechanics, balance, and gameplay changes.'],
            ['name' => 'Visuals', 'slug' => 'visuals', 'description' => 'Textures, shaders, models, and visual upgrades.'],
            ['name' => 'Quality of Life', 'slug' => 'quality-of-life', 'description' => 'Small improvements that make playing smoother.'],
            ['name' => 'Tools', 'slug' => 'tools', 'description' => 'Utilities, launchers, editors, and helper tools.'],
        ])->each(fn (array $category) => Category::query()->updateOrCreate(
            ['slug' => $category['slug']],
            [
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true,
            ],
        ));
    }
}
