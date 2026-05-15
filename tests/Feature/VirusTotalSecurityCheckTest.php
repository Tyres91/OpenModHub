<?php

namespace Tests\Feature;

use App\Jobs\PollVirusTotalResultJob;
use App\Jobs\SubmitUrlToVirusTotalJob;
use App\Models\Category;
use App\Models\Mod;
use App\Models\SecurityCheck;
use App\Models\User;
use App\Services\VirusTotalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VirusTotalSecurityCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_job_creates_pending_check_and_queues_poll_job(): void
    {
        Queue::fake();
        config(['services.virustotal.enabled' => true, 'services.virustotal.api_key' => 'test-key']);
        Http::fake([
            'example.com/*' => Http::response([], 200),
            'https://www.virustotal.com/api/v3/urls' => Http::response([
                'data' => ['id' => 'analysis-123'],
            ]),
        ]);

        $mod = $this->mod();

        (new SubmitUrlToVirusTotalJob($mod->id))->handle(app(VirusTotalService::class));

        $this->assertDatabaseHas('security_checks', [
            'mod_id' => $mod->id,
            'status' => SecurityCheck::STATUS_PENDING,
            'analysis_id' => 'analysis-123',
        ]);

        Queue::assertPushed(PollVirusTotalResultJob::class);
    }

    public function test_polling_completed_clean_analysis_marks_check_clean(): void
    {
        config(['services.virustotal.enabled' => true, 'services.virustotal.api_key' => 'test-key']);
        Http::fake([
            '*/download' => Http::response([], 200),
            'https://www.virustotal.com/api/v3/analyses/analysis-123' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'completed',
                        'stats' => [
                            'malicious' => 0,
                            'suspicious' => 0,
                            'harmless' => 12,
                            'undetected' => 3,
                        ],
                    ],
                ],
            ]),
        ]);

        $securityCheck = $this->mod()->securityChecks()->create([
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_PENDING,
            'external_url' => 'https://example.com/download',
            'analysis_id' => 'analysis-123',
        ]);

        app(VirusTotalService::class)->pollAnalysis($securityCheck);

        $this->assertDatabaseHas('security_checks', [
            'id' => $securityCheck->id,
            'status' => SecurityCheck::STATUS_CLEAN,
            'result_summary' => '0 malicious, 0 suspicious, 12 harmless, 3 undetected.',
        ]);
    }

    public function test_polling_completed_suspicious_analysis_marks_check_suspicious(): void
    {
        config(['services.virustotal.enabled' => true, 'services.virustotal.api_key' => 'test-key']);
        Http::fake([
            '*/download' => Http::response([], 200),
            'https://www.virustotal.com/api/v3/analyses/analysis-123' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'completed',
                        'stats' => [
                            'malicious' => 1,
                            'suspicious' => 0,
                            'harmless' => 12,
                            'undetected' => 3,
                        ],
                    ],
                ],
            ]),
        ]);

        $securityCheck = $this->mod()->securityChecks()->create([
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_PENDING,
            'external_url' => 'https://example.com/download',
            'analysis_id' => 'analysis-123',
        ]);

        app(VirusTotalService::class)->pollAnalysis($securityCheck);

        $this->assertDatabaseHas('security_checks', [
            'id' => $securityCheck->id,
            'status' => SecurityCheck::STATUS_SUSPICIOUS,
        ]);
    }

    private function mod(): Mod
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        return Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Security Test Mod',
            'slug' => 'security-test-mod-'.uniqid(),
            'description' => 'A mod used to test VirusTotal security checks.',
            'external_download_url' => 'https://example.com/download',
            'status' => Mod::STATUS_PENDING,
        ]);
    }
}
