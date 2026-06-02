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
        Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
            'sort_order' => 20,
        ]);

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
            'sort_order' => 30,
        ]);
    }

    public function test_admin_index_orders_categories_by_sort_order(): void
    {
        $admin = $this->userWithRole('admin');
        Category::query()->create(['name' => 'Visuals', 'slug' => 'visuals', 'is_active' => true, 'sort_order' => 20]);
        Category::query()->create(['name' => 'Audio', 'slug' => 'audio', 'is_active' => true, 'sort_order' => 10]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('categories.0.slug', 'audio')
                ->where('categories.1.slug', 'visuals')
            );
    }

    public function test_admin_can_reorder_categories(): void
    {
        $admin = $this->userWithRole('admin');
        $audio = Category::query()->create(['name' => 'Audio', 'slug' => 'audio', 'is_active' => true, 'sort_order' => 10]);
        $visuals = Category::query()->create(['name' => 'Visuals', 'slug' => 'visuals', 'is_active' => true, 'sort_order' => 20]);

        $this->actingAs($admin)->patch(route('admin.categories.reorder'), [
            'categories' => [
                ['id' => $visuals->id, 'sort_order' => 10],
                ['id' => $audio->id, 'sort_order' => 20],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $visuals->id, 'sort_order' => 10]);
        $this->assertDatabaseHas('categories', ['id' => $audio->id, 'sort_order' => 20]);
    }

    public function test_regular_user_cannot_reorder_categories(): void
    {
        $user = $this->userWithRole('user');
        $category = Category::query()->create(['name' => 'Audio', 'slug' => 'audio', 'is_active' => true, 'sort_order' => 10]);

        $this->actingAs($user)->patch(route('admin.categories.reorder'), [
            'categories' => [
                ['id' => $category->id, 'sort_order' => 20],
            ],
        ])->assertForbidden();

        $this->assertSame(10, $category->fresh()->sort_order);
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
