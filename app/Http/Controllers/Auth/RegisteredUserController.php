<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\TurnstileCaptcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request, TurnstileCaptcha $captcha): Response
    {
        $registerRequest = RegisterUserRequest::createFrom($request);
        $requiresCaptcha = $registerRequest->requiresCaptcha();

        return Inertia::render('Auth/Register', [
            'requiresCaptcha' => $requiresCaptcha,
            'turnstileSiteKey' => $requiresCaptcha ? $captcha->siteKey() : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::query()->where('slug', 'user')->first();

        if ($role !== null) {
            $user->roles()->attach($role);
        }

        event(new Registered($user));

        Auth::login($user);

        $request->clearRegistrationLimiter();

        if (Setting::get('debug_mode', '0') === '1') {
            return redirect(route('verification.notice', absolute: false))
                ->with('debug_verification_url', URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $user->getKey(),
                        'hash' => sha1($user->getEmailForVerification()),
                    ],
                ));
        }

        return redirect(route('dashboard', absolute: false));
    }
}
