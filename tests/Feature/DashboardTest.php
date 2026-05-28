<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Permission;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_sees_dashboard_without_moderation_metrics(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('metrics', null)
                ->where('moderationTodos', null)
                ->where('canSeeUserMetrics', false)
            );
    }

    public function test_user_with_moderation_permission_sees_moderation_metrics_without_user_metrics(): void
    {
        $moderator = $this->userWithPermission('review_mods');
        $this->seedDashboardData();

        $this->actingAs($moderator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.pending_mods', 1)
                ->where('metrics.pending_versions', 1)
                ->where('metrics.pending_reports', 1)
                ->where('moderationTodos.pending_mods', 1)
                ->where('moderationTodos.pending_versions', 1)
                ->where('moderationTodos.pending_reports', 1)
                ->where('moderationTodos.total', 3)
                ->where('metrics.visible_comments', 1)
                ->where('metrics.approved_mods', 2)
                ->where('metrics.approved_mods_last_7_days', 1)
                ->missing('metrics.total_users')
                ->where('canSeeUserMetrics', false)
            );
    }

    public function test_admin_sees_user_metrics(): void
    {
        Permission::query()->create(['name' => 'Manage Users', 'slug' => 'manage_users', 'group' => 'community']);
        $admin = $this->userWithRole('admin');
        User::factory()->create(['created_at' => now()->subDays(10)]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.total_users', 2)
                ->where('metrics.new_users_last_7_days', 1)
                ->where('auth.user.permissions.0', 'manage_users')
                ->where('canSeeUserMetrics', true)
            );
    }

    private function seedDashboardData(): void
    {
        $owner = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        $approvedRecent = $this->mod($owner, $category, Mod::STATUS_APPROVED, now());
        $this->mod($owner, $category, Mod::STATUS_APPROVED, now()->subDays(10));
        $this->mod($owner, $category, Mod::STATUS_PENDING, null);

        ModVersion::query()->create([
            'mod_id' => $approvedRecent->id,
            'submitted_by' => $owner->id,
            'version' => '1.1.0',
            'normalized_version' => '1.1.0.0',
            'changelog' => 'Pending dashboard test version.',
            'external_download_url' => 'https://example.com/mod-version',
            'status' => Mod::STATUS_PENDING,
        ]);

        Comment::query()->create([
            'user_id' => $owner->id,
            'mod_id' => $approvedRecent->id,
            'body' => 'Visible comment.',
            'status' => Comment::STATUS_VISIBLE,
        ]);

        Comment::query()->create([
            'user_id' => $owner->id,
            'mod_id' => $approvedRecent->id,
            'body' => 'Hidden comment.',
            'status' => Comment::STATUS_HIDDEN,
        ]);

        Report::query()->create([
            'user_id' => $owner->id,
            'mod_id' => $approvedRecent->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        Report::query()->create([
            'user_id' => $owner->id,
            'mod_id' => $approvedRecent->id,
            'reason' => 'other',
            'status' => Report::STATUS_RESOLVED,
        ]);
    }

    private function mod(User $owner, Category $category, string $status, mixed $approvedAt): Mod
    {
        return Mod::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => ucfirst($status).' Dashboard Mod '.uniqid(),
            'slug' => $status.'-dashboard-mod-'.uniqid(),
            'description' => 'Dashboard metric test mod.',
            'external_download_url' => 'https://example.com/mod',
            'status' => $status,
            'approved_at' => $approvedAt,
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
}
