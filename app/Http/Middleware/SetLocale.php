<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('locales.available', []));

        $locale = $this->resolveLocale($request, $available);

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request, array $available): string
    {
        $urlLocale = $request->query('locale');

        if ($urlLocale && in_array($urlLocale, $available, true)) {
            return $urlLocale;
        }

        $user = $request->user();

        if ($user && $user->locale && in_array($user->locale, $available, true)) {
            return $user->locale;
        }

        $sessionLocale = $request->session()->get('locale');

        if ($sessionLocale && in_array($sessionLocale, $available, true)) {
            return $sessionLocale;
        }

        $defaultLocale = Setting::get('default_locale', config('locales.default'));

        if ($defaultLocale && in_array($defaultLocale, $available, true)) {
            return $defaultLocale;
        }

        return config('locales.default');
    }
}
