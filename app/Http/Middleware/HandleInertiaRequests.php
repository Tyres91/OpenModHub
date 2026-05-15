<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'locale' => $request->user()->locale,
                    'roles' => $request->user()->roles()->pluck('slug')->all(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
                'debugVerificationUrl' => fn () => $request->session()->get('debug_verification_url'),
            ],
            'locale' => app()->getLocale(),
            'defaultLocale' => Setting::get('default_locale', config('locales.default')),
            'availableLocales' => config('locales.available', []),
            'googleTagManagerId' => Setting::get('google_tag_manager_id', ''),
            'branding' => [
                'logoUrl' => $this->siteLogoUrl(),
                'logoText' => Setting::get('site_logo_text', 'OpenModHub'),
                'showLogoText' => Setting::get('site_logo_show_text', '1') === '1',
            ],
            'translations' => $this->loadTranslations(app()->getLocale()),
        ];
    }

    private function siteLogoUrl(): ?string
    {
        $path = Setting::get('site_logo_path', '');

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    private function loadTranslations(string $locale): array
    {
        $path = lang_path("{$locale}/messages.php");

        if (! file_exists($path)) {
            return [];
        }

        return require $path;
    }
}
