<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create(['name' => 'testuser', 'email' => 'test@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users', 2)
        );
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $editor = User::factory()->create();
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);
        $editor->roles()->attach($editorRole);

        $response = $this->actingAs($editor)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $userRole = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $user->roles()->attach($userRole);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_user_name_and_email(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create(['name' => 'oldname', 'email' => 'old@example.com']);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'newname',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'newname',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_update_user_locale(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create(['locale' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'de',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'de',
        ]);
    }

    public function test_admin_can_update_user_password(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirect();

        $this->assertTrue(auth()->guard()->once([
            'email' => $user->email,
            'password' => 'new-secure-password',
        ]));
    }

    public function test_admin_can_change_user_roles(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);
        $userRole = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create();
        $user->roles()->attach($userRole);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => ['editor', 'user'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->hasRole('editor'));
        $this->assertTrue($user->fresh()->hasRole('user'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_admin_can_assign_and_remove_special_rank(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);
        $user = User::factory()->create();
        $specialRank = Rank::query()->create([
            'name' => 'Founder',
            'required_published_mods' => 0,
            'required_points' => 0,
            'color' => '#dc2626',
            'is_special' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'rank_id' => $specialRank->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'rank_id' => $specialRank->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'rank_id' => null,
        ])->assertRedirect();

        $this->assertNull($user->fresh()->rank_id);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $userRole = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => ['user'],
        ]);

        $response->assertSessionHasErrors('roles');
    }

    public function test_admin_cannot_remove_last_admin_from_system(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $userRole = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => ['user'],
        ]);

        $response->assertSessionHasErrors('roles');
    }

    public function test_admin_can_remove_admin_role_from_other_user_when_other_admins_exist(): void
    {
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $userRole = Role::query()->create(['name' => 'User', 'slug' => 'user']);
        $admin1->roles()->attach($adminRole);
        $admin2->roles()->attach($adminRole);

        $response = $this->actingAs($admin1)->patch(route('admin.users.update', $admin2), [
            'name' => $admin2->name,
            'email' => $admin2->email,
            'roles' => ['user'],
        ]);

        $response->assertRedirect();
        $this->assertFalse($admin2->fresh()->hasRole('admin'));
        $this->assertTrue($admin2->fresh()->hasRole('user'));
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.block', $user), [
            'block_reason' => 'Spam reports',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'blocked_by' => $admin->id,
            'block_reason' => 'Spam reports',
        ]);
        $this->assertNotNull($user->fresh()->blocked_at);

        $this->actingAs($admin)->patch(route('admin.users.unblock', $user))->assertRedirect();

        $this->assertNull($user->fresh()->blocked_at);
        $this->assertNull($user->fresh()->blocked_by);
        $this->assertNull($user->fresh()->block_reason);
    }

    public function test_admin_cannot_block_own_account(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)->patch(route('admin.users.block', $admin), [
            'block_reason' => 'Self block',
        ])->assertSessionHasErrors('user');

        $this->assertNull($admin->fresh()->blocked_at);
    }

    public function test_admin_cannot_block_last_unblocked_admin(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole);
        $otherAdmin = User::factory()->create(['blocked_at' => now()]);
        $otherAdmin->roles()->attach($adminRole);

        $this->actingAs($admin)->patch(route('admin.users.block', $admin), [
            'block_reason' => 'Last admin',
        ])->assertSessionHasErrors('user');

        $this->assertNull($admin->fresh()->blocked_at);
    }

    public function test_blocked_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => bcrypt('password'),
            'blocked_at' => now(),
        ]);

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
