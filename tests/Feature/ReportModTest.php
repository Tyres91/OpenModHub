<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Mod;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_approved_mod(): void
    {
        $user = $this->userWithRole('user');
        $mod = $this->approvedMod();

        $this->actingAs($user)->post(route('mods.reports.store', $mod), [
            'reason' => 'broken_link',
            'message' => 'The download link returns a 404.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'mod_id' => $mod->id,
            'reason' => 'broken_link',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_user_cannot_report_pending_mod(): void
    {
        $user = $this->userWithRole('user');
        $mod = $this->pendingMod();

        $this->actingAs($user)->post(route('mods.reports.store', $mod), [
            'reason' => 'spam',
        ])->assertForbidden();
    }

    public function test_editor_can_review_reports(): void
    {
        $editor = $this->userWithRole('editor');
        $mod = $this->approvedMod();
        $report = Report::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $mod->id,
            'reason' => 'malware',
            'message' => 'This mod contains suspicious files.',
            'status' => Report::STATUS_PENDING,
        ]);

        $this->actingAs($editor)->patch(route('admin.reports.resolve', $report))->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_RESOLVED,
            'reviewed_by' => $editor->id,
        ]);
    }

    public function test_editor_can_dismiss_reports(): void
    {
        $editor = $this->userWithRole('editor');
        $mod = $this->approvedMod();
        $report = Report::query()->create([
            'user_id' => User::factory()->create()->id,
            'mod_id' => $mod->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        $this->actingAs($editor)->patch(route('admin.reports.dismiss', $report))->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_DISMISSED,
            'reviewed_by' => $editor->id,
        ]);
    }

    public function test_regular_user_cannot_access_reports_admin(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
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
            'title' => ucfirst($status).' Report Test Mod',
            'slug' => $status.'-report-mod-'.uniqid(),
            'description' => 'A test mod for report functionality.',
            'external_download_url' => 'https://example.com/report-mod',
            'status' => $status,
            'approved_at' => $status === Mod::STATUS_APPROVED ? now() : null,
        ]);
    }
}
