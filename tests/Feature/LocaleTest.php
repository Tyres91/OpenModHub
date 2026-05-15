<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_uses_backend_default_locale(): void
    {
        Setting::set('default_locale', 'de');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'de')
            ->where('defaultLocale', 'de')
        );
    }

    public function test_guest_can_set_session_locale(): void
    {
        Setting::set('default_locale', 'en');

        $response = $this->post(route('locale.update'), ['locale' => 'de']);
        $response->assertRedirect();

        $response = $this->get('/');
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'de')
        );
    }

    public function test_user_with_null_locale_uses_backend_default(): void
    {
        Setting::set('default_locale', 'de');
        $user = $this->userWithRole('user');

        $this->actingAs($user)->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'de')
            );
    }

    public function test_user_locale_overrides_backend_default(): void
    {
        Setting::set('default_locale', 'de');
        $user = $this->userWithRole('user');
        $user->update(['locale' => 'en']);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
            );
    }

    public function test_user_can_reset_locale_to_system_default(): void
    {
        $user = $this->userWithRole('user');
        $user->update(['locale' => 'de']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => null,
        ]);
    }

    public function test_user_can_set_locale_in_profile(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'de',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'de',
        ]);
    }

    public function test_admin_can_change_default_locale(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'default_locale' => 'de',
        ])->assertRedirect();

        $this->assertEquals('de', Setting::get('default_locale'));
    }

    public function test_editor_cannot_change_default_locale(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->patch(route('admin.settings.update'), [
            'default_locale' => 'de',
        ])->assertForbidden();
    }

    public function test_user_cannot_change_default_locale(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)->patch(route('admin.settings.update'), [
            'default_locale' => 'de',
        ])->assertForbidden();
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }

    public function test_inertia_shares_translations(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->has('translations')
                ->has('availableLocales')
                ->where('defaultLocale', 'en')
            );
    }

    private function userWithRole(string $slug): User
    {
        $role = Role::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
        ]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
