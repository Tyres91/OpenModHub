<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\RankPointRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_rank(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.ranks.store'), [
            'name' => 'Starter Modder',
            'required_points' => 50,
            'color' => '#0ea5e9',
            'icon' => 'star',
            'is_special' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ranks', [
            'name' => 'Starter Modder',
            'required_points' => 50,
            'color' => '#0ea5e9',
            'icon' => 'star',
            'is_special' => false,
        ]);
    }

    public function test_admin_can_update_rank(): void
    {
        $admin = $this->userWithRole('admin');
        $rank = Rank::query()->create([
            'name' => 'Trusted',
            'required_published_mods' => 25,
            'required_points' => 25,
            'color' => '#16a34a',
            'icon' => 'shield-check',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.ranks.update', $rank), [
            'name' => 'Trusted Creator',
            'required_points' => 300,
            'color' => '#22c55e',
            'icon' => 'badge-check',
            'is_special' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ranks', [
            'id' => $rank->id,
            'name' => 'Trusted Creator',
            'required_points' => 300,
            'is_special' => true,
        ]);
    }

    public function test_admin_can_update_rank_point_rules(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.rank-point-rules.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/RankPointRules/Index')
                ->has('pointRules')
            );

        $response = $this->actingAs($admin)->patch(route('admin.rank-point-rules.update'), [
            'rules' => [
                [
                    'key' => RankPointRule::COMMENT_CREATED,
                    'points' => 7,
                    'threshold' => null,
                    'is_enabled' => true,
                ],
                [
                    'key' => RankPointRule::DOWNLOAD_THRESHOLD,
                    'points' => 120,
                    'threshold' => 500,
                    'is_enabled' => true,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rank_point_rules', [
            'key' => RankPointRule::COMMENT_CREATED,
            'points' => 7,
        ]);
        $this->assertDatabaseHas('rank_point_rules', [
            'key' => RankPointRule::DOWNLOAD_THRESHOLD,
            'points' => 120,
            'threshold' => 500,
        ]);
    }

    public function test_editor_cannot_manage_rank_point_rules(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.rank-point-rules.index'))
            ->assertForbidden();
    }

    public function test_editor_cannot_manage_ranks(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.ranks.index'))
            ->assertForbidden();
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
