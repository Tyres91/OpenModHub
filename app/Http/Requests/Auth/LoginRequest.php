<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\WarningService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->string('login')->toString());
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if (! Auth::attempt([$field => $login, 'password' => $this->string('password')->toString()], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if ($user instanceof User) {
            $warningService = app(WarningService::class);
            $accountLock = $warningService->getActiveAccountLock($user);

            if (! $user->isBlocked() && $accountLock === null) {
                RateLimiter::clear($this->throttleKey());

                return;
            }

            $blockReason = $user->block_reason;
            $blockedUntil = $user->blocked_until;

            if ($accountLock !== null) {
                $blockReason = $accountLock->reason;
                $blockedUntil = $accountLock->expires_at;
            }

            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            $message = $blockedUntil !== null && $blockedUntil->isFuture()
                ? __('messages.sanctions.account_locked', [
                    'date' => $blockedUntil->format('d.m.Y H:i'),
                    'reason' => $blockReason ?? '',
                ])
                : __('messages.sanctions.account_locked_permanent', [
                    'reason' => $blockReason ?? '',
                ]);

            throw ValidationException::withMessages([
                'login' => $message,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower(trim($this->string('login')->toString())).'|'.$this->ip());
    }
}
