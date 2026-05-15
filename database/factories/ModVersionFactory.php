<?php

namespace Database\Factories;

use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModVersion>
 */
class ModVersionFactory extends Factory
{
    protected $model = ModVersion::class;

    public function definition(): array
    {
        return [
            'mod_id' => \App\Models\Mod::factory()->approved(),
            'submitted_by' => \App\Models\User::factory(),
            'version' => '1.0.0',
            'normalized_version' => '1.0.0.0',
            'changelog' => 'Initial release',
            'external_download_url' => $this->faker->url(),
            'virus_total_url' => null,
            'download_clicks_count' => 0,
            'status' => Mod::STATUS_PENDING,
            'rejection_reason' => null,
            'approved_at' => null,
            'reviewed_by' => null,
            'is_current' => false,
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

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
