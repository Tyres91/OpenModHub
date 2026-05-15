<?php

namespace App\Jobs;

use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SecurityCheck;
use App\Services\VirusTotalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitUrlToVirusTotalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $modId, public ?int $modVersionId = null) {}

    public function handle(VirusTotalService $virusTotalService): void
    {
        $modVersion = $this->modVersionId !== null ? ModVersion::query()->find($this->modVersionId) : null;

        if ($modVersion !== null) {
            $securityCheck = $virusTotalService->submitVersionUrl($modVersion);

            if ($securityCheck->status !== SecurityCheck::STATUS_PENDING || blank($securityCheck->analysis_id)) {
                return;
            }

            PollVirusTotalResultJob::dispatch($securityCheck->id)
                ->delay(now()->addSeconds((int) config('services.virustotal.poll_delay_seconds', 90)));

            return;
        }

        $mod = Mod::query()->find($this->modId);

        if (! $mod) {
            return;
        }

        $securityCheck = $virusTotalService->submitUrl($mod);

        if ($securityCheck->status !== SecurityCheck::STATUS_PENDING || blank($securityCheck->analysis_id)) {
            return;
        }

        PollVirusTotalResultJob::dispatch($securityCheck->id)
            ->delay(now()->addSeconds((int) config('services.virustotal.poll_delay_seconds', 90)));
    }
}
