<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const LEGAL_KEYS = [
        'legal_operator_name',
        'legal_represented_by',
        'legal_street',
        'legal_postal_code',
        'legal_city',
        'legal_country',
        'legal_email',
        'legal_phone',
        'legal_vat_id',
        'legal_privacy_contact',
        'legal_additional_info',
    ];

    public function index(): Response
    {
        Gate::authorize('manageSettings', Setting::class);

        return Inertia::render('Admin/Settings/Index', [
            'defaultLocale' => Setting::get('default_locale', config('locales.default')),
            'availableLocales' => config('locales.available', []),
            'googleTagManagerId' => Setting::get('google_tag_manager_id', ''),
            'debugMode' => Setting::get('debug_mode', '0') === '1',
            'modSubmissionsBlocked' => Setting::get('mod_submissions_blocked', '0') === '1',
            'modPendingSubmissionLimit' => (int) Setting::get('mod_pending_submission_limit', 5),
            'siteLogoUrl' => $this->siteLogoUrl(),
            'siteLogoText' => Setting::get('site_logo_text', 'OpenModHub'),
            'siteLogoShowText' => Setting::get('site_logo_show_text', '1') === '1',
            'legalSettings' => collect(self::LEGAL_KEYS)
                ->mapWithKeys(fn (string $key): array => [$key => Setting::get($key, '')])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manageSettings', Setting::class);

        $validated = $request->validate([
            'default_locale' => ['required', 'string', Rule::in(array_keys(config('locales.available')))],
            'google_tag_manager_id' => ['nullable', 'string', 'max:32', 'regex:/^GTM-[A-Z0-9]+$/'],
            'debug_mode' => ['sometimes', 'boolean'],
            'mod_submissions_blocked' => ['sometimes', 'boolean'],
            'mod_pending_submission_limit' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'site_logo_text' => ['nullable', 'string', 'max:80'],
            'site_logo_show_text' => ['sometimes', 'boolean'],
            'legal_operator_name' => ['nullable', 'string', 'max:255'],
            'legal_represented_by' => ['nullable', 'string', 'max:255'],
            'legal_street' => ['nullable', 'string', 'max:255'],
            'legal_postal_code' => ['nullable', 'string', 'max:32'],
            'legal_city' => ['nullable', 'string', 'max:255'],
            'legal_country' => ['nullable', 'string', 'max:255'],
            'legal_email' => ['nullable', 'email', 'max:255'],
            'legal_phone' => ['nullable', 'string', 'max:255'],
            'legal_vat_id' => ['nullable', 'string', 'max:255'],
            'legal_privacy_contact' => ['nullable', 'string', 'max:255'],
            'legal_additional_info' => ['nullable', 'string', 'max:5000'],
        ]);

        Setting::set('default_locale', $validated['default_locale']);
        Setting::set('google_tag_manager_id', $validated['google_tag_manager_id'] ?? '');
        Setting::set('debug_mode', $request->boolean('debug_mode') ? '1' : '0');
        Setting::set('mod_submissions_blocked', $request->boolean('mod_submissions_blocked') ? '1' : '0');
        Setting::set('mod_pending_submission_limit', (string) ($validated['mod_pending_submission_limit'] ?? Setting::get('mod_pending_submission_limit', 5)));
        Setting::set('site_logo_text', $validated['site_logo_text'] ?? '');
        Setting::set('site_logo_show_text', $request->boolean('site_logo_show_text') ? '1' : '0');

        foreach (self::LEGAL_KEYS as $key) {
            Setting::set($key, $validated[$key] ?? '');
        }

        return back()->with('status', __('messages.flash.settings_updated'));
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        Gate::authorize('manageSettings', Setting::class);

        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $oldLogoPath = Setting::get('site_logo_path', '');
        if (filled($oldLogoPath)) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        $path = $validated['logo']->store('branding', 'public');
        Setting::set('site_logo_path', $path);

        return back()->with('status', __('messages.flash.logo_updated'));
    }

    public function destroyLogo(): RedirectResponse
    {
        Gate::authorize('manageSettings', Setting::class);

        $oldLogoPath = Setting::get('site_logo_path', '');
        if (filled($oldLogoPath)) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        Setting::set('site_logo_path', '');

        return back()->with('status', __('messages.flash.logo_deleted'));
    }

    private function siteLogoUrl(): ?string
    {
        $path = Setting::get('site_logo_path', '');

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
