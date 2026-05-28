<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SecurityCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_review_permission_can_approve_pending_mod(): void
    {
        $reviewer = $this->userWithPermission('review_mods');
        $mod = $this->pendingMod();

        $response = $this->actingAs($reviewer)->patch(route('admin.moderation.approve', $mod));

        $response->assertRedirect();
        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'status' => Mod::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
        ]);
        $this->assertNotNull($mod->refresh()->approved_at);
    }

    public function test_user_with_review_permission_can_reject_pending_mod_with_reason(): void
    {
        $reviewer = $this->userWithPermission('review_mods');
        $mod = $this->pendingMod();

        $response = $this->actingAs($reviewer)->patch(route('admin.moderation.reject', $mod), [
            'rejection_reason' => 'The external download link is not reachable.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => 'The external download link is not reachable.',
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_regular_user_cannot_access_moderation_queue(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)
            ->get(route('admin.moderation.index'))
            ->assertForbidden();
    }

    public function test_moderation_queue_includes_latest_security_check(): void
    {
        $reviewer = $this->userWithPermission('review_mods');
        $mod = $this->pendingMod();

        $mod->securityChecks()->create([
            'provider' => SecurityCheck::PROVIDER_VIRUSTOTAL,
            'status' => SecurityCheck::STATUS_CLEAN,
            'external_url' => $mod->external_download_url,
            'result_summary' => '0 malicious, 0 suspicious, 12 harmless, 3 undetected.',
            'checked_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->get(route('admin.moderation.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mods.data.0.security_check.status', SecurityCheck::STATUS_CLEAN)
                ->where('mods.data.0.security_check.result_summary', '0 malicious, 0 suspicious, 12 harmless, 3 undetected.')
            );
    }

    public function test_user_with_review_permission_can_approve_pending_mod_version_and_make_it_current(): void
    {
        $reviewer = $this->userWithPermission('review_mods');
        $mod = $this->approvedMod();
        $current = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $mod->user_id,
            'version' => '1.0.0',
            'normalized_version' => '1.0.0.0',
            'changelog' => 'Initial release.',
            'external_download_url' => 'https://example.com/current',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now()->subDay(),
            'is_current' => true,
        ]);
        $pending = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $mod->user_id,
            'version' => '1.1.0-beta1',
            'normalized_version' => '1.1.0.0-beta1',
            'changelog' => 'Beta release with reviewed changes.',
            'external_download_url' => 'https://example.com/beta',
            'status' => Mod::STATUS_PENDING,
        ]);

        $response = $this->actingAs($reviewer)->patch(route('admin.moderation.versions.approve', $pending));

        $response->assertRedirect();
        $this->assertFalse($current->refresh()->is_current);
        $this->assertTrue($pending->refresh()->is_current);
        $this->assertSame(Mod::STATUS_APPROVED, $pending->status);
        $this->assertDatabaseHas('mods', [
            'id' => $mod->id,
            'external_download_url' => 'https://example.com/beta',
        ]);
    }

    public function test_user_with_review_permission_can_reject_pending_mod_version_with_reason(): void
    {
        $reviewer = $this->userWithPermission('review_mods');
        $mod = $this->approvedMod();
        $pending = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $mod->user_id,
            'version' => '1.1.0',
            'normalized_version' => '1.1.0.0',
            'changelog' => 'Release with reviewed changes.',
            'external_download_url' => 'https://example.com/version',
            'status' => Mod::STATUS_PENDING,
        ]);

        $this->actingAs($reviewer)->patch(route('admin.moderation.versions.reject', $pending), [
            'rejection_reason' => 'The new download link is not reachable.',
        ])->assertRedirect();

        $this->assertDatabaseHas('mod_versions', [
            'id' => $pending->id,
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => 'The new download link is not reachable.',
            'reviewed_by' => $reviewer->id,
        ]);
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

    private function userWithPermission(string $slug): User
    {
        $user = User::factory()->create();
        $permission = Permission::query()->create([
            'name' => ucwords(str_replace('_', ' ', $slug)),
            'slug' => $slug,
            'group' => 'moderation',
        ]);
        $user->permissions()->attach($permission);

        return $user;
    }

    private function pendingMod(): Mod
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        return Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Pending Combat Tweak',
            'slug' => 'pending-combat-tweak',
            'description' => 'A pending mod ready for editorial review.',
            'external_download_url' => 'https://example.com/pending-combat-tweak',
            'status' => Mod::STATUS_PENDING,
        ]);
    }

    private function approvedMod(): Mod
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        return Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Combat Tweak',
            'slug' => 'approved-combat-tweak',
            'description' => 'An approved mod ready for version review.',
            'external_download_url' => 'https://example.com/approved-combat-tweak',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
