<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', array_keys(config('locales.available')))],
        ]);

        $locale = $validated['locale'];

        if ($request->user()) {
            if ($locale === config('locales.default') && ! $request->filled('force')) {
                $request->user()->update(['locale' => null]);
            } else {
                $request->user()->update(['locale' => $locale]);
            }
        } else {
            $request->session()->put('locale', $locale);
        }

        return redirect('/?locale='.$locale);
    }
}
