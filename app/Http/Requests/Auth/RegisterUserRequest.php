<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\TurnstileCaptcha;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisterUserRequest extends FormRequest
{
    public const CAPTCHA_THRESHOLD = 3;

    public const MAX_ATTEMPTS = 10;

    public const DECAY_SECONDS = 3600;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'website' => ['nullable', 'prohibited'],
            'registration_started_at' => ['required', 'integer'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->tooManyRegistrationAttempts()) {
                $validator->errors()->add('email', __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($this->throttleKey()),
                    'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
                ]));

                return;
            }

            if (! $this->waitedLongEnough()) {
                $validator->errors()->add('email', __('messages.auth.registration_failed'));
            }

            if ($this->requiresCaptcha() && ! app(TurnstileCaptcha::class)->verify($this->string('turnstile_token')->toString(), $this->ip())) {
                $validator->errors()->add('turnstile_token', __('messages.auth.captcha_failed'));
            }
        });
    }

    public function requiresCaptcha(): bool
    {
        return RateLimiter::attempts($this->throttleKey()) >= self::CAPTCHA_THRESHOLD
            && app(TurnstileCaptcha::class)->isConfigured();
    }

    public function hitRegistrationLimiter(): void
    {
        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
    }

    public function clearRegistrationLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function throttleKey(): string
    {
        return 'register|'.Str::transliterate((string) $this->ip());
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->hitRegistrationLimiter();

        parent::failedValidation($validator);
    }

    private function tooManyRegistrationAttempts(): bool
    {
        return RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS);
    }

    private function waitedLongEnough(): bool
    {
        $startedAt = (int) $this->input('registration_started_at');

        return $startedAt > 0 && now()->getTimestampMs() - $startedAt >= 3000;
    }
}
