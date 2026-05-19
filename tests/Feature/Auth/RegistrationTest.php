<?php

namespace Tests\Feature\Auth;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', $this->validRegistrationData());

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertAuthenticated();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionMissing('debug_verification_url');
    }

    public function test_debug_mode_shows_registration_verification_url(): void
    {
        Notification::fake();
        Setting::set('debug_mode', '1');

        $response = $this->post('/register', $this->validRegistrationData());

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $debugVerificationUrl = $response->baseResponse->getSession()->get('debug_verification_url');

        $response->assertRedirect(route('verification.notice', absolute: false));
        $response->assertSessionHas('debug_verification_url');
        $this->assertIsString($debugVerificationUrl);
        $this->assertStringContainsString('/verify-email/'.$user->id.'/', $debugVerificationUrl);
        $this->assertStringContainsString('signature=', $debugVerificationUrl);
        $this->assertAuthenticatedAs($user);
    }

    public function test_honeypot_blocks_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'website' => 'https://spam.example',
        ]));

        $response->assertSessionHasErrors('website');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_too_fast_registration_is_rejected(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'registration_started_at' => now()->getTimestampMs(),
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_captcha_is_required_after_failed_registration_attempts(): void
    {
        config()->set('services.turnstile.site_key', 'site-key');
        config()->set('services.turnstile.secret_key', 'secret-key');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/register', $this->validRegistrationData([
                'email' => 'not-an-email',
            ]))->assertSessionHasErrors('email');
        }

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('requiresCaptcha', true)
                ->where('turnstileSiteKey', 'site-key')
            );
    }

    public function test_captcha_must_pass_after_failed_registration_attempts(): void
    {
        config()->set('services.turnstile.site_key', 'site-key');
        config()->set('services.turnstile.secret_key', 'secret-key');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/register', $this->validRegistrationData([
                'email' => 'not-an-email',
            ]));
        }

        $this->post('/register', $this->validRegistrationData())
            ->assertSessionHasErrors('turnstile_token');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);

        $this->post('/register', $this->validRegistrationData([
            'turnstile_token' => 'valid-token',
        ]))->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /** @param array<string, mixed> $overrides */
    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'registration_started_at' => now()->subSeconds(5)->getTimestampMs(),
        ], $overrides);
    }
}
