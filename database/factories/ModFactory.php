<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Mod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mod>
 */
class ModFactory extends Factory
{
    protected $model = Mod::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'title' => $title,
            'slug' => \Str::slug($title),
            'description' => $this->faker->paragraph(),
            'external_download_url' => $this->faker->url(),
            'virus_total_url' => null,
            'download_clicks_count' => 0,
            'status' => Mod::STATUS_PENDING,
            'rejection_reason' => null,
            'approved_at' => null,
            'reviewed_by' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => 'Does not meet guidelines',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Mod::STATUS_PENDING,
        ]);
    }
}
