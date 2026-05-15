<?php

namespace App\Services;

use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SecurityCheck;
use Illuminate\Support\Facades\Http;

class VirusTotalService
{
    private const BASE_URL = 'https://www.virustotal.com/api/v3';

    public function isConfigured(): bool
    {
        return filter_var(config('services.virustotal.enabled'), FILTER_VALIDATE_BOOL)
            && filled(config('services.virustotal.api_key'));
    }

    private function validateUrl(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Invalid URL format.';
        }

        try {
            $headResponse = Http::timeout(5)->head($url);

            if ($headResponse->failed()) {
                return 'URL not reachable (HEAD request failed with status '.$headResponse->status().').';
            }
        } catch (\Exception $e) {
            return 'URL validation failed: '.$e->getMessage();
        }

        return null;
    }

    private function recordFailed(Mod $mod, string $reason): SecurityCheck
    {
        return $mod->securityChecks()->create([
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_FAILED,
            'external_url' => $mod->external_download_url,
            'result_summary' => $reason,
            'checked_at' => now(),
        ]);
    }

    private function recordVersionFailed(ModVersion $modVersion, string $reason): SecurityCheck
    {
        return $modVersion->securityChecks()->create([
            'mod_id' => $modVersion->mod_id,
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_FAILED,
            'external_url' => $modVersion->external_download_url,
            'result_summary' => $reason,
            'checked_at' => now(),
        ]);
    }

    public function recordNotSubmitted(Mod $mod): SecurityCheck
    {
        return $mod->securityChecks()->create([
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_NOT_SUBMITTED,
            'external_url' => $mod->external_download_url,
            'result_summary' => 'VirusTotal API is not configured.',
        ]);
    }

    public function recordVersionNotSubmitted(ModVersion $modVersion): SecurityCheck
    {
        return $modVersion->securityChecks()->create([
            'mod_id' => $modVersion->mod_id,
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_NOT_SUBMITTED,
            'external_url' => $modVersion->external_download_url,
            'result_summary' => 'VirusTotal API is not configured.',
        ]);
    }

    public function submitUrl(Mod $mod): SecurityCheck
    {
        if (! $this->isConfigured()) {
            return $this->recordNotSubmitted($mod);
        }

        $validationResult = $this->validateUrl($mod->external_download_url);
        if ($validationResult !== null) {
            return $this->recordFailed($mod, $validationResult);
        }

        return $this->submitToVirusTotal($mod, null);
    }

    public function submitVersionUrl(ModVersion $modVersion): SecurityCheck
    {
        if (! $this->isConfigured()) {
            return $this->recordVersionNotSubmitted($modVersion);
        }

        $validationResult = $this->validateUrl($modVersion->external_download_url);
        if ($validationResult !== null) {
            return $this->recordVersionFailed($modVersion, $validationResult);
        }

        return $this->submitToVirusTotal(null, $modVersion);
    }

    private function submitToVirusTotal(?Mod $mod = null, ?ModVersion $modVersion = null): SecurityCheck
    {
        $url = $mod?->external_download_url ?? $modVersion->external_download_url;
        $modId = $mod?->id ?? $modVersion->mod_id;

        $securityCheck = $mod
            ? $mod->securityChecks()->create([
                'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
                'status' => SecurityCheck::STATUS_PENDING,
                'external_url' => $url,
            ])
            : $modVersion->securityChecks()->create([
                'mod_id' => $modId,
                'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
                'status' => SecurityCheck::STATUS_PENDING,
                'external_url' => $url,
            ]);

        $response = Http::withHeader('x-apikey', config('services.virustotal.api_key'))
            ->asForm()
            ->timeout(10)
            ->post(self::BASE_URL.'/urls', ['url' => $url]);

        if (! $response->successful()) {
            $securityCheck->update([
                'status' => SecurityCheck::STATUS_FAILED,
                'result_summary' => 'VirusTotal URL submission failed.',
                'raw_response' => $response->json(),
                'checked_at' => now(),
            ]);

            return $securityCheck;
        }

        $securityCheck->update([
            'analysis_id' => $response->json('data.id'),
            'result_summary' => 'Submitted to VirusTotal; result pending.',
            'raw_response' => $response->json(),
        ]);

        return $securityCheck;
    }

    public function pollAnalysis(SecurityCheck $securityCheck): SecurityCheck
    {
        if (! $this->isConfigured()) {
            $securityCheck->update([
                'status' => SecurityCheck::STATUS_NOT_SUBMITTED,
                'result_summary' => 'VirusTotal API is not configured.',
                'checked_at' => now(),
            ]);

            return $securityCheck;
        }

        if (blank($securityCheck->analysis_id)) {
            $securityCheck->update([
                'status' => SecurityCheck::STATUS_FAILED,
                'result_summary' => 'VirusTotal analysis ID is missing.',
                'checked_at' => now(),
            ]);

            return $securityCheck;
        }

        $response = Http::withHeader('x-apikey', config('services.virustotal.api_key'))
            ->timeout(10)
            ->get(self::BASE_URL.'/analyses/'.$securityCheck->analysis_id);

        if (! $response->successful()) {
            $securityCheck->update([
                'status' => SecurityCheck::STATUS_FAILED,
                'result_summary' => 'VirusTotal analysis polling failed.',
                'raw_response' => $response->json(),
                'checked_at' => now(),
            ]);

            return $securityCheck;
        }

        $rawResponse = $response->json();
        $attributes = $rawResponse['data']['attributes'] ?? [];

        if (($attributes['status'] ?? null) !== 'completed') {
            $securityCheck->update([
                'status' => SecurityCheck::STATUS_PENDING,
                'result_summary' => 'VirusTotal result is still pending.',
                'raw_response' => $rawResponse,
            ]);

            return $securityCheck;
        }

        $stats = $attributes['stats'] ?? [];
        $malicious = (int) ($stats['malicious'] ?? 0);
        $suspicious = (int) ($stats['suspicious'] ?? 0);
        $harmless = (int) ($stats['harmless'] ?? 0);
        $undetected = (int) ($stats['undetected'] ?? 0);

        $securityCheck->update([
            'status' => ($malicious > 0 || $suspicious > 0) ? SecurityCheck::STATUS_SUSPICIOUS : SecurityCheck::STATUS_CLEAN,
            'result_summary' => "{$malicious} malicious, {$suspicious} suspicious, {$harmless} harmless, {$undetected} undetected.",
            'raw_response' => $rawResponse,
            'checked_at' => now(),
        ]);

        return $securityCheck;
    }
}
