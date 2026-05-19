<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class EmailTemplateService
{
    public function getSubject(string $key, string $locale = 'en'): string
    {
        $template = EmailTemplate::get($key);

        if ($template === null) {
            return $this->getDefaultSubject($key, $locale);
        }

        return $template->getSubject($locale);
    }

    public function renderBody(string $key, array $data, string $locale = 'en'): string
    {
        $template = EmailTemplate::get($key);

        if ($template === null) {
            return $this->getDefaultBody($key, $data, $locale);
        }

        $body = $template->renderBody($data, $locale);

        return $this->applyGlobalPlaceholders($body, $data);
    }

    /** @return array<string, string> */
    public function getPlaceholders(string $key): array
    {
        return EmailTemplate::PLACEHOLDERS[$key] ?? [];
    }

    public function getLogoUrl(): ?string
    {
        $path = Setting::get('site_logo_path', '');

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function getSiteName(): string
    {
        return Setting::get('site_logo_text', 'OpenModHub');
    }

    public function getSiteUrl(): string
    {
        return config('app.url');
    }

    /** @return array<string, string> */
    public function getLegalInfo(): array
    {
        return [
            'operator' => Setting::get('legal_operator_name', ''),
            'street' => Setting::get('legal_street', ''),
            'postal_code' => Setting::get('legal_postal_code', ''),
            'city' => Setting::get('legal_city', ''),
            'country' => Setting::get('legal_country', ''),
            'email' => Setting::get('legal_email', ''),
            'phone' => Setting::get('legal_phone', ''),
        ];
    }

    private function applyGlobalPlaceholders(string $body, array $data): string
    {
        $globals = [
            '{site_name}' => $this->getSiteName(),
            '{site_url}' => $this->getSiteUrl(),
        ];

        foreach ($globals as $placeholder => $value) {
            if (! isset($data[trim($placeholder, '{}')])) {
                $body = str_replace($placeholder, $value, $body);
            }
        }

        return $body;
    }

    private function getDefaultSubject(string $key, string $locale): string
    {
        $defaults = [
            EmailTemplate::KEY_VERIFY_EMAIL => [
                'en' => 'Verify your email address',
                'de' => 'Bestätige deine E-Mail-Adresse',
            ],
            EmailTemplate::KEY_MOD_APPROVED => [
                'en' => 'Your mod has been approved',
                'de' => 'Deine Mod wurde genehmigt',
            ],
            EmailTemplate::KEY_MOD_REJECTED => [
                'en' => 'Your mod was rejected',
                'de' => 'Deine Mod wurde abgelehnt',
            ],
            EmailTemplate::KEY_VERSION_APPROVED => [
                'en' => 'New version approved',
                'de' => 'Neue Version genehmigt',
            ],
            EmailTemplate::KEY_VERSION_REJECTED => [
                'en' => 'New version rejected',
                'de' => 'Neue Version abgelehnt',
            ],
        ];

        return $defaults[$key][$locale] ?? $defaults[$key]['en'];
    }

    private function getDefaultBody(string $key, array $data, string $locale): string
    {
        $user_name = $data['user_name'] ?? 'User';
        $site_name = $this->getSiteName();

        $defaults = [
            EmailTemplate::KEY_VERIFY_EMAIL => [
                'en' => "Hello {$user_name},\n\nwelcome to {$site_name}! Please verify your email address to get started.\n\n{cta_text} → {cta_url}",
                'de' => "Hallo {$user_name},\n\nwillkommen bei {$site_name}! Bitte bestätige deine E-Mail-Adresse, um loszulegen.\n\n{cta_text} → {cta_url}",
            ],
            EmailTemplate::KEY_MOD_APPROVED => [
                'en' => "Hello {$user_name},\n\ngreat news! Your mod \"{mod_title}\" has been approved and is now publicly visible.\n\n{cta_text} → {cta_url}",
                'de' => "Hallo {$user_name},\n\ngute Neuigkeiten! Deine Mod \"{mod_title}\" wurde genehmigt und ist jetzt öffentlich sichtbar.\n\n{cta_text} → {cta_url}",
            ],
            EmailTemplate::KEY_MOD_REJECTED => [
                'en' => "Hello {$user_name},\n\nunfortunately your mod \"{mod_title}\" was rejected.\n\nReason: {rejection_reason}\n\nYou can revise and resubmit your mod.\n\n{cta_text} → {cta_url}",
                'de' => "Hallo {$user_name},\n\nleider wurde deine Mod \"{mod_title}\" abgelehnt.\n\nBegründung: {rejection_reason}\n\nDu kannst deine Mod überarbeiten und erneut einreichen.\n\n{cta_text} → {cta_url}",
            ],
            EmailTemplate::KEY_VERSION_APPROVED => [
                'en' => "Hello {$user_name},\n\nthe new version \"{version}\" of your mod \"{mod_title}\" has been approved.\n\n{cta_text} → {cta_url}",
                'de' => "Hallo {$user_name},\n\ndie neue Version \"{version}\" deiner Mod \"{mod_title}\" wurde genehmigt.\n\n{cta_text} → {cta_url}",
            ],
            EmailTemplate::KEY_VERSION_REJECTED => [
                'en' => "Hello {$user_name},\n\nthe new version \"{version}\" of your mod \"{mod_title}\" was rejected.\n\nReason: {rejection_reason}\n\nYou can revise and resubmit.\n\n{cta_text} → {cta_url}",
                'de' => "Hallo {$user_name},\n\ndie neue Version \"{version}\" deiner Mod \"{mod_title}\" wurde abgelehnt.\n\nBegründung: {rejection_reason}\n\nDu kannst sie überarbeiten und erneut einreichen.\n\n{cta_text} → {cta_url}",
            ],
        ];

        $body = $defaults[$key][$locale] ?? $defaults[$key]['en'];

        return $this->applyGlobalPlaceholders($body, $data);
    }
}
