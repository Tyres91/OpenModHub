<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Audio',
            'description' => 'Soundtracks, effects, and audio improvements.',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Audio',
            'slug' => 'audio',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = $this->userWithRole('admin');
        $category = Category::query()->create([
            'name' => 'Tools',
            'slug' => 'tools',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Tools',
            'description' => 'Editors, launchers, and helper utilities.',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => 'Editors, launchers, and helper utilities.',
            'is_active' => false,
        ]);
    }

    public function test_regular_user_cannot_manage_categories(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
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
