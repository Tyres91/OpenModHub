<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileCaptcha
{
    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function isConfigured(): bool
    {
        return filled($this->siteKey()) && filled(config('services.turnstile.secret_key'));
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! $this->isConfigured() || blank($token)) {
            return false;
        }

        $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return $response->ok() && $response->json('success') === true;
    }
}
