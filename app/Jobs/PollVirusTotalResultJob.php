<?php

namespace App\Jobs;

use App\Models\SecurityCheck;
use App\Services\VirusTotalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollVirusTotalResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $securityCheckId) {}

    public function handle(VirusTotalService $virusTotalService): void
    {
        $securityCheck = SecurityCheck::query()->find($this->securityCheckId);

        if (! $securityCheck) {
            return;
        }

        $virusTotalService->pollAnalysis($securityCheck);
    }
}
