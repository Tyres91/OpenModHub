<?php

namespace Database\Factories;

use App\Models\SecurityCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityCheck>
 */
class SecurityCheckFactory extends Factory
{
    protected $model = SecurityCheck::class;

    public function definition(): array
    {
        return [
            'mod_id' => \App\Models\Mod::factory(),
            'mod_version_id' => null,
            'provider' => 'virustotal',
            'status' => SecurityCheck::STATUS_PENDING,
            'external_url' => $this->faker->url(),
            'analysis_id' => null,
            'result_summary' => null,
            'raw_response' => null,
            'checked_at' => null,
        ];
    }

    public function asRoot(): static
    {
        return $this->state(fn (array $attributes) => [
            'mod_id' => null,
            'mod_version_id' => null,
        ]);
    }

    public function clean(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityCheck::STATUS_CLEAN,
            'result_summary' => '0 malicious, 0 suspicious, 5 harmless, 0 undetected.',
            'checked_at' => now(),
        ]);
    }

    public function suspicious(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityCheck::STATUS_SUSPICIOUS,
            'result_summary' => '2 malicious, 1 suspicious, 3 harmless, 0 undetected.',
            'checked_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityCheck::STATUS_FAILED,
            'result_summary' => 'VirusTotal API request failed.',
            'checked_at' => now(),
        ]);
    }

    public function notSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityCheck::STATUS_NOT_SUBMITTED,
            'result_summary' => 'VirusTotal API is not configured.',
        ]);
    }
}
