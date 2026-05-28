<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const FAVICON_FILES = [
        'favicon.ico',
        'favicon-16x16.png',
        'favicon-32x32.png',
        'apple-touch-icon.png',
        'android-chrome-192x192.png',
        'android-chrome-512x512.png',
        'site.webmanifest',
    ];

    private array $faviconFileBackups = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::FAVICON_FILES as $file) {
            $path = public_path($file);
            $this->faviconFileBackups[$file] = file_exists($path) ? file_get_contents($path) : null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->faviconFileBackups as $file => $contents) {
            $path = public_path($file);

            if ($contents === null) {
                if (file_exists($path)) {
                    unlink($path);
                }

                continue;
            }

            file_put_contents($path, $contents);
        }

        parent::tearDown();
    }

    public function test_admin_can_update_tracking_and_legal_settings(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'default_locale' => 'de',
            'google_tag_manager_id' => 'GTM-ABC1234',
            'debug_mode' => true,
            'mod_submissions_blocked' => true,
            'mod_pending_submission_limit' => 7,
            'site_logo_text' => 'Open Mod Hub',
            'site_logo_show_text' => false,
            'legal_operator_name' => 'OpenModHub GmbH',
            'legal_represented_by' => 'Jane Doe',
            'legal_street' => 'Example Street 1',
            'legal_postal_code' => '12345',
            'legal_city' => 'Berlin',
            'legal_country' => 'Germany',
            'legal_email' => 'privacy@example.com',
            'legal_phone' => '+49 123 456',
            'legal_vat_id' => 'DE123456789',
            'legal_privacy_contact' => 'privacy@example.com',
            'legal_additional_info' => 'Additional legal text.',
        ])->assertRedirect();

        $this->assertEquals('GTM-ABC1234', Setting::get('google_tag_manager_id'));
        $this->assertEquals('1', Setting::get('debug_mode'));
        $this->assertEquals('1', Setting::get('mod_submissions_blocked'));
        $this->assertEquals('7', Setting::get('mod_pending_submission_limit'));
        $this->assertEquals('Open Mod Hub', Setting::get('site_logo_text'));
        $this->assertEquals('0', Setting::get('site_logo_show_text'));
        $this->assertEquals('OpenModHub GmbH', Setting::get('legal_operator_name'));
        $this->assertEquals('privacy@example.com', Setting::get('legal_privacy_contact'));
    }

    public function test_admin_can_upload_and_remove_logo(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');
        $logo = UploadedFile::fake()->image('logo.png', 300, 120)->size(100);

        $this->actingAs($admin)->post(route('admin.settings.logo.update'), [
            'logo' => $logo,
        ])->assertRedirect();

        $path = Setting::get('site_logo_path');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)->delete(route('admin.settings.logo.destroy'))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertSame('', Setting::get('site_logo_path'));
    }

    public function test_non_image_logo_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.settings.logo.update'), [
            'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_regular_user_cannot_upload_logo(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('user');

        $this->actingAs($user)->post(route('admin.settings.logo.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 120)->size(100),
        ])->assertForbidden();
    }

    public function test_branding_is_shared_to_inertia(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('logo.png', 300, 120)->size(100)->store('branding', 'public');
        Setting::set('site_logo_path', $path);
        Setting::set('site_logo_text', 'Custom Hub');
        Setting::set('site_logo_show_text', '0');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branding.logoUrl', Storage::disk('public')->url($path))
                ->where('branding.logoText', 'Custom Hub')
                ->where('branding.showLogoText', false)
            );
    }

    public function test_admin_settings_page_shares_submission_settings(): void
    {
        Setting::set('mod_submissions_blocked', '1');
        Setting::set('mod_pending_submission_limit', '9');

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->where('modSubmissionsBlocked', true)
                ->where('modPendingSubmissionLimit', 9)
            );
    }

    public function test_admin_settings_page_shares_debug_mode_state(): void
    {
        Setting::set('debug_mode', '1');

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->where('debugMode', true)
            );
    }

    public function test_invalid_google_tag_manager_id_is_rejected(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'default_locale' => 'en',
            'google_tag_manager_id' => '<script>alert(1)</script>',
        ])->assertSessionHasErrors('google_tag_manager_id');
    }

    public function test_invalid_mod_submission_limit_is_rejected(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'default_locale' => 'en',
            'mod_pending_submission_limit' => -1,
        ])->assertSessionHasErrors('mod_pending_submission_limit');
    }

    public function test_regular_user_cannot_update_tracking_or_legal_settings(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)->patch(route('admin.settings.update'), [
            'default_locale' => 'en',
            'google_tag_manager_id' => 'GTM-ABC1234',
            'legal_operator_name' => 'OpenModHub GmbH',
        ])->assertForbidden();
    }

    public function test_google_tag_manager_id_is_shared_but_not_loaded_in_server_html(): void
    {
        Setting::set('google_tag_manager_id', 'GTM-ABC1234');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('googleTagManagerId', 'GTM-ABC1234'));
        $this->assertStringNotContainsString('googletagmanager.com/gtm.js', $response->getContent());
        $this->assertStringNotContainsString('googletagmanager.com/ns.html', $response->getContent());
    }

    public function test_legal_pages_are_publicly_reachable(): void
    {
        Setting::set('legal_operator_name', 'OpenModHub GmbH');
        Setting::set('legal_email', 'privacy@example.com');

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Legal/Imprint')
                ->where('legalSettings.operator_name', 'OpenModHub GmbH')
            );

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Legal/Privacy')
                ->where('legalSettings.email', 'privacy@example.com')
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
