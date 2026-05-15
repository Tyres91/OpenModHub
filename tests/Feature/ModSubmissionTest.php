<?php

namespace Tests\Feature;

use App\Jobs\SubmitUrlToVirusTotalJob;
use App\Models\Category;
use App\Models\Mod;
use App\Models\ModImage;
use App\Models\ModVersion;
use App\Models\Rating;
use App\Models\Role;
use App\Models\SecurityCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_mod_for_review(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $user->roles()->attach($role);
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(100);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Better Inventory Sorting',
            'description' => 'Adds a cleaner inventory sorting experience with stable categories and faster item lookup.',
            'version' => '1.0.0',
            'changelog' => 'Initial release with the first public feature set.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/downloads/better-inventory',
            'virus_total_url' => 'https://www.virustotal.com/gui/url/example',
            'image' => $image,
        ]);

        $response->assertRedirect(route('mods.mine'));

        $this->assertDatabaseHas('mods', [
            'title' => 'Better Inventory Sorting',
            'status' => Mod::STATUS_PENDING,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseCount('mod_images', 1);

        $this->assertDatabaseHas('mod_versions', [
            'version' => '1.0.0',
            'normalized_version' => '1.0.0.0',
            'status' => Mod::STATUS_PENDING,
            'external_download_url' => 'https://example.com/downloads/better-inventory',
        ]);

        $this->assertDatabaseHas('mod_images', [
            'mod_id' => Mod::query()->where('title', 'Better Inventory Sorting')->first()->id,
        ]);

        $modImage = ModImage::query()->whereHas('mod', fn ($q) => $q->where('title', 'Better Inventory Sorting'))->first();
        $this->assertNotNull($modImage->file_path);
    }

    public function test_authenticated_user_without_explicit_role_can_open_submit_form(): void
    {
        $user = User::factory()->create();
        Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('mods.create'));

        $response->assertOk();
    }

    public function test_authenticated_user_without_explicit_role_can_submit_mod(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(100);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'No Role Mod',
            'description' => 'This mod is submitted by a user without an explicit role assignment.',
            'version' => '1.0.0-beta1',
            'changelog' => 'Initial beta release with enough changelog text.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/downloads/no-role-mod',
            'virus_total_url' => 'https://www.virustotal.com/gui/url/example',
            'image' => $image,
        ]);

        $response->assertRedirect(route('mods.mine'));

        $this->assertDatabaseHas('mods', [
            'title' => 'No Role Mod',
            'status' => Mod::STATUS_PENDING,
            'user_id' => $user->id,
        ]);
    }

    public function test_only_approved_mods_are_publicly_viewable(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Visuals',
            'slug' => 'visuals',
            'is_active' => true,
        ]);

        $approved = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Sharper Textures',
            'slug' => 'sharper-textures',
            'description' => 'A publicly approved texture improvement mod.',
            'external_download_url' => 'https://example.com/sharper-textures',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $pending = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Pending Texture Pack',
            'slug' => 'pending-texture-pack',
            'description' => 'A pending mod that should not be public yet.',
            'external_download_url' => 'https://example.com/pending-textures',
            'status' => Mod::STATUS_PENDING,
        ]);

        $this->get(route('mods.show', $approved))->assertOk();
        $this->get(route('mods.show', $pending))->assertForbidden();
    }

    public function test_download_redirect_counts_once_per_session(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Visuals',
            'slug' => 'visuals',
            'is_active' => true,
        ]);

        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Downloadable Mod',
            'slug' => 'downloadable-mod',
            'description' => 'A publicly approved mod with a tracked external download link.',
            'external_download_url' => 'https://example.com/downloadable-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->get(route('mods.download', $mod))
            ->assertRedirect('https://example.com/downloadable-mod');

        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'download_clicks_count' => 1,
        ]);

        $this->get(route('mods.download', $mod))
            ->assertRedirect('https://example.com/downloadable-mod');

        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'download_clicks_count' => 1,
        ]);
    }

    public function test_guest_cannot_download_pending_mod(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Visuals',
            'slug' => 'visuals',
            'is_active' => true,
        ]);

        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Pending Download Mod',
            'slug' => 'pending-download-mod',
            'description' => 'A pending mod that should not be downloadable by guests.',
            'external_download_url' => 'https://example.com/pending-download-mod',
            'status' => Mod::STATUS_PENDING,
        ]);

        $this->get(route('mods.download', $mod))->assertForbidden();

        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'download_clicks_count' => 0,
        ]);
    }

    public function test_owner_can_submit_semantic_prerelease_version_for_approved_mod(): void
    {
        config(['services.virustotal.enabled' => false, 'services.virustotal.api_key' => null]);

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'A publicly approved mod ready for a new version.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('mods.versions.store', $mod), [
            'version' => 'v1.2.0-beta1',
            'changelog' => 'Adds a beta release with meaningful changes.',
            'external_download_url' => 'https://example.com/approved-mod-beta',
            'virus_total_url' => 'https://www.virustotal.com/gui/url/example',
        ]);

        $response->assertRedirect(route('mods.mine'));

        $this->assertDatabaseHas('mod_versions', [
            'mod_id' => $mod->id,
            'version' => 'v1.2.0-beta1',
            'normalized_version' => '1.2.0.0-beta1',
            'status' => Mod::STATUS_PENDING,
            'is_current' => false,
        ]);
    }

    public function test_invalid_version_string_is_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'A publicly approved mod ready for a new version.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->actingAs($user)->post(route('mods.versions.store', $mod), [
            'version' => 'latest final',
            'changelog' => 'Adds a release with meaningful changes.',
            'external_download_url' => 'https://example.com/approved-mod-final',
        ])->assertSessionHasErrors('version');

        $this->assertDatabaseMissing('mod_versions', [
            'mod_id' => $mod->id,
            'version' => 'latest final',
        ]);
    }

    public function test_version_download_counts_once_per_session(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'A publicly approved mod with a version.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $version = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $user->id,
            'version' => '1.0.0',
            'normalized_version' => '1.0.0.0',
            'changelog' => 'Initial release.',
            'external_download_url' => 'https://example.com/approved-mod-v1',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
            'is_current' => true,
        ]);

        $this->get(route('mods.versions.download', [$mod, $version]))
            ->assertRedirect('https://example.com/approved-mod-v1');
        $this->get(route('mods.versions.download', [$mod, $version]))
            ->assertRedirect('https://example.com/approved-mod-v1');

        $this->assertDatabaseHas('mod_versions', [
            'id' => $version->id,
            'download_clicks_count' => 1,
        ]);
    }

    public function test_public_mods_can_be_sorted_by_download_clicks_descending(): void
    {
        [$first, $second] = $this->approvedModsForSorting([
            ['title' => 'Low Downloads Mod', 'slug' => 'low-downloads-mod', 'download_clicks_count' => 1],
            ['title' => 'High Downloads Mod', 'slug' => 'high-downloads-mod', 'download_clicks_count' => 10],
        ]);

        $this->get(route('mods.index', ['sort_by' => 'downloads', 'sort_direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mods.data.0.id', $second->id)
                ->where('mods.data.1.id', $first->id)
            );
    }

    public function test_public_mods_can_be_sorted_by_title_ascending(): void
    {
        [$first, $second] = $this->approvedModsForSorting([
            ['title' => 'Zebra Mod', 'slug' => 'zebra-mod'],
            ['title' => 'Alpha Mod', 'slug' => 'alpha-mod'],
        ]);

        $this->get(route('mods.index', ['sort_by' => 'title', 'sort_direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mods.data.0.id', $second->id)
                ->where('mods.data.1.id', $first->id)
            );
    }

    public function test_public_mods_can_be_sorted_by_rating_descending(): void
    {
        [$lowRated, $highRated] = $this->approvedModsForSorting([
            ['title' => 'Low Rated Mod', 'slug' => 'low-rated-mod'],
            ['title' => 'High Rated Mod', 'slug' => 'high-rated-mod'],
        ]);

        Rating::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $lowRated->id,
            'score' => 2,
        ]);

        Rating::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $highRated->id,
            'score' => 5,
        ]);

        $this->get(route('mods.index', ['sort_by' => 'rating', 'sort_direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mods.data.0.id', $highRated->id)
                ->where('mods.data.1.id', $lowRated->id)
            );
    }

    public function test_invalid_public_mod_sort_parameters_fall_back_to_newest_descending(): void
    {
        [$older, $newer] = $this->approvedModsForSorting([
            ['title' => 'Older Mod', 'slug' => 'older-mod', 'approved_at' => now()->subDays(2)],
            ['title' => 'Newer Mod', 'slug' => 'newer-mod', 'approved_at' => now()],
        ]);

        $this->get(route('mods.index', ['sort_by' => 'invalid', 'sort_direction' => 'sideways']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort_by', 'approved_at')
                ->where('filters.sort_direction', 'desc')
                ->where('mods.data.0.id', $newer->id)
                ->where('mods.data.1.id', $older->id)
            );
    }

    public function test_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Test Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_rejects_wrong_image_format(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('screenshot.gif', 800, 600)->size(100);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Test Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download',
            'image' => $image,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_rejects_image_below_minimum_resolution(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('small.jpg', 256, 256)->size(100);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Test Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download',
            'image' => $image,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_rejects_image_exceeding_max_size(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('large.jpg', 800, 600)->size(6000);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Test Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download',
            'image' => $image,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_accepts_png_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('screenshot.png', 800, 600)->size(100);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'PNG Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'version' => '1.0.0',
            'changelog' => 'Initial release with enough changelog text.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download',
            'image' => $image,
        ]);

        $response->assertRedirect(route('mods.mine'));
    }

    public function test_submission_records_not_submitted_security_check_when_virustotal_is_disabled(): void
    {
        Storage::fake('public');
        config(['services.virustotal.enabled' => false, 'services.virustotal.api_key' => null]);

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Unchecked Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'version' => '1.0.0',
            'changelog' => 'Initial release with enough changelog text.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download-unchecked',
            'image' => UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(100),
        ]);

        $response->assertRedirect(route('mods.mine'));

        $this->assertDatabaseHas('security_checks', [
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_NOT_SUBMITTED,
            'external_url' => 'https://example.com/download-unchecked',
        ]);
    }

    public function test_submission_queues_virustotal_check_when_configured(): void
    {
        Storage::fake('public');
        Queue::fake();
        config(['services.virustotal.enabled' => true, 'services.virustotal.api_key' => 'test-key']);

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('mods.store'), [
            'title' => 'Queued Mod',
            'description' => 'A test mod description with enough characters to pass validation.',
            'version' => '1.0.0',
            'changelog' => 'Initial release with enough changelog text.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/download-queued',
            'image' => UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(100),
        ]);

        $response->assertRedirect(route('mods.mine'));

        Queue::assertPushed(SubmitUrlToVirusTotalJob::class);
    }

    public function test_regular_user_cannot_submit_when_pending_mod_limit_is_reached(): void
    {
        Storage::fake('public');
        Setting::set('mod_pending_submission_limit', '5');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Limit',
            'slug' => 'limit',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Mod::query()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => 'Pending Mod '.$i,
                'slug' => 'pending-mod-'.$i,
                'description' => 'A pending mod used for limit testing.',
                'external_download_url' => 'https://example.com/pending-'.$i,
                'status' => Mod::STATUS_PENDING,
            ]);
        }

        $this->actingAs($user)->post(route('mods.store'), $this->validModPayload($category))
            ->assertForbidden();
    }

    public function test_approved_and_rejected_mods_do_not_count_against_pending_limit(): void
    {
        Storage::fake('public');
        Setting::set('mod_pending_submission_limit', '1');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Resolved Limit',
            'slug' => 'resolved-limit',
            'is_active' => true,
        ]);

        foreach ([Mod::STATUS_APPROVED, Mod::STATUS_REJECTED] as $index => $status) {
            Mod::query()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => 'Resolved Mod '.$index,
                'slug' => 'resolved-mod-'.$index,
                'description' => 'A resolved mod used for limit testing.',
                'external_download_url' => 'https://example.com/resolved-'.$index,
                'status' => $status,
                'approved_at' => $status === Mod::STATUS_APPROVED ? now() : null,
            ]);
        }

        $this->actingAs($user)->post(route('mods.store'), $this->validModPayload($category))
            ->assertRedirect(route('mods.mine'));
    }

    public function test_zero_pending_limit_means_unlimited(): void
    {
        Storage::fake('public');
        Setting::set('mod_pending_submission_limit', '0');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Unlimited',
            'slug' => 'unlimited',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            Mod::query()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => 'Unlimited Pending '.$i,
                'slug' => 'unlimited-pending-'.$i,
                'description' => 'A pending mod used for unlimited limit testing.',
                'external_download_url' => 'https://example.com/unlimited-'.$i,
                'status' => Mod::STATUS_PENDING,
            ]);
        }

        $this->actingAs($user)->post(route('mods.store'), $this->validModPayload($category))
            ->assertRedirect(route('mods.mine'));
    }

    public function test_global_submission_block_only_blocks_regular_users(): void
    {
        Storage::fake('public');
        Setting::set('mod_submissions_blocked', '1');

        $category = Category::query()->create([
            'name' => 'Blocked Uploads',
            'slug' => 'blocked-uploads',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $editor = User::factory()->create();
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);
        $editor->roles()->attach($editorRole);

        $this->actingAs($user)->post(route('mods.store'), $this->validModPayload($category, 'Regular Blocked Mod'))
            ->assertForbidden();

        $this->actingAs($editor)->post(route('mods.store'), $this->validModPayload($category, 'Editor Allowed Mod'))
            ->assertRedirect(route('mods.mine'));
    }

    public function test_blocked_user_cannot_submit_mod(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['blocked_at' => now()]);
        $category = Category::query()->create([
            'name' => 'Blocked User',
            'slug' => 'blocked-user',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('mods.store'), $this->validModPayload($category))
            ->assertRedirect('/');
    }

    /**
     * @param  array<int, array<string, mixed>>  $mods
     * @return array<int, Mod>
     */
    private function approvedModsForSorting(array $mods): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Sorting',
            'slug' => 'sorting',
            'is_active' => true,
        ]);

        return array_map(function (array $attributes) use ($user, $category): Mod {
            return Mod::query()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => $attributes['title'],
                'slug' => $attributes['slug'],
                'description' => 'A publicly approved mod used to test sorting behavior.',
                'external_download_url' => 'https://example.com/'.$attributes['slug'],
                'download_clicks_count' => $attributes['download_clicks_count'] ?? 0,
                'status' => Mod::STATUS_APPROVED,
                'approved_at' => $attributes['approved_at'] ?? now(),
            ]);
        }, $mods);
    }

    /** @return array<string, mixed> */
    private function validModPayload(Category $category, string $title = 'Limited Test Mod'): array
    {
        return [
            'title' => $title,
            'description' => 'A test mod description with enough characters to pass validation.',
            'version' => '1.0.0',
            'changelog' => 'Initial release with enough changelog text.',
            'category_id' => $category->id,
            'external_download_url' => 'https://example.com/'.str($title)->slug()->toString(),
            'image' => UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(100),
        ];
    }
}
