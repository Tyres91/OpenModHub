<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Mod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_rate_approved_mod_once_and_update_rating(): void
    {
        $user = $this->userWithRole('user');
        $mod = $this->approvedMod();

        $this->actingAs($user)->post(route('mods.ratings.store', $mod), [
            'score' => 4,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('mods.ratings.store', $mod), [
            'score' => 5,
        ])->assertRedirect();

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'mod_id' => $mod->id,
            'score' => 5,
        ]);
    }

    public function test_user_cannot_rate_pending_mod(): void
    {
        $user = $this->userWithRole('user');
        $mod = $this->pendingMod();

        $this->actingAs($user)->post(route('mods.ratings.store', $mod), [
            'score' => 4,
        ])->assertForbidden();
    }

    public function test_user_can_comment_on_approved_mod(): void
    {
        $user = $this->userWithRole('user');
        $mod = $this->approvedMod();

        $this->actingAs($user)->post(route('mods.comments.store', $mod), [
            'body' => 'This mod works well and the install instructions are clear.',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'mod_id' => $mod->id,
            'status' => Comment::STATUS_VISIBLE,
        ]);
    }

    public function test_editor_can_hide_comment(): void
    {
        $editor = $this->userWithRole('editor');
        $comment = Comment::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $this->approvedMod()->id,
            'body' => 'Needs moderation.',
            'status' => Comment::STATUS_VISIBLE,
        ]);

        $this->actingAs($editor)->patch(route('comments.hide', $comment))->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => Comment::STATUS_HIDDEN,
            'moderated_by' => $editor->id,
        ]);
    }

    public function test_editor_can_show_hidden_comment(): void
    {
        $editor = $this->userWithRole('editor');
        $comment = Comment::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $this->approvedMod()->id,
            'body' => 'Previously hidden comment.',
            'status' => Comment::STATUS_HIDDEN,
        ]);

        $this->actingAs($editor)->patch(route('comments.show', $comment))->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => Comment::STATUS_VISIBLE,
            'moderated_by' => $editor->id,
        ]);
    }

    public function test_regular_user_cannot_show_hidden_comment(): void
    {
        $user = $this->userWithRole('user');
        $comment = Comment::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $this->approvedMod()->id,
            'body' => 'Hidden comment.',
            'status' => Comment::STATUS_HIDDEN,
        ]);

        $this->actingAs($user)->patch(route('comments.show', $comment))->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => Comment::STATUS_HIDDEN,
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

    private function approvedMod(): Mod
    {
        return $this->mod(Mod::STATUS_APPROVED);
    }

    private function pendingMod(): Mod
    {
        return $this->mod(Mod::STATUS_PENDING);
    }

    private function mod(string $status): Mod
    {
        $category = Category::query()->firstOrCreate([
            'slug' => 'gameplay',
        ], [
            'name' => 'Gameplay',
            'is_active' => true,
        ]);

        return Mod::query()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'title' => ucfirst($status).' Community Mod',
            'slug' => $status.'-community-mod-'.uniqid(),
            'description' => 'A community interaction test mod with enough detail.',
            'external_download_url' => 'https://example.com/community-mod',
            'status' => $status,
            'approved_at' => $status === Mod::STATUS_APPROVED ? now() : null,
        ]);
    }
}
