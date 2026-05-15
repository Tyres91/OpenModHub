<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Rank;
use App\Models\RankPointRule;
use App\Models\Rating;
use App\Models\User;
use App\Services\RankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankPointCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_points_are_calculated_retroactively_from_activity(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);

        Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'An approved mod that grants rank points.',
            'external_download_url' => 'https://example.com/approved-mod',
            'download_clicks_count' => 1000,
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        Comment::query()->create([
            'user_id' => $user->id,
            'mod_id' => Mod::query()->firstOrFail()->id,
            'body' => 'A visible comment that grants points.',
            'status' => Comment::STATUS_VISIBLE,
        ]);

        $this->assertSame(125, app(RankService::class)->pointsForUser($user));

        RankPointRule::query()->where('key', RankPointRule::COMMENT_CREATED)->update(['points' => 10]);

        $this->assertSame(130, app(RankService::class)->pointsForUser($user));
    }

    public function test_highest_matching_normal_rank_is_selected_by_points(): void
    {
        $user = User::factory()->create();
        Rank::query()->create([
            'name' => 'Starter',
            'required_published_mods' => 10,
            'required_points' => 10,
            'color' => '#2563eb',
        ]);
        Rank::query()->create([
            'name' => 'Trusted',
            'required_published_mods' => 20,
            'required_points' => 20,
            'color' => '#16a34a',
        ]);
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'An approved mod that grants rank points.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $payload = app(RankService::class)->userRankPayload($user);

        $this->assertSame(20, $payload['points']);
        $this->assertSame('Trusted', $payload['rank']['name']);
    }

    public function test_special_rank_assignment_is_not_overwritten_by_points(): void
    {
        $specialRank = Rank::query()->create([
            'name' => 'Founder',
            'required_published_mods' => 1,
            'required_points' => 0,
            'color' => '#dc2626',
            'is_special' => true,
        ]);
        Rank::query()->create([
            'name' => 'High Score',
            'required_published_mods' => 20,
            'required_points' => 20,
            'color' => '#16a34a',
        ]);
        $user = User::factory()->create(['rank_id' => $specialRank->id]);
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Approved Mod',
            'slug' => 'approved-mod',
            'description' => 'An approved mod that grants rank points.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $payload = app(RankService::class)->userRankPayload($user);

        $this->assertSame('Founder', $payload['rank']['name']);
        $this->assertTrue($payload['is_special_rank_locked']);
    }

    public function test_approved_new_versions_grant_points_but_initial_versions_do_not(): void
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
            'description' => 'An approved mod with version history.',
            'external_download_url' => 'https://example.com/approved-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $user->id,
            'version' => '1.0.0',
            'normalized_version' => '1.0.0.0',
            'changelog' => 'Initial release.',
            'external_download_url' => 'https://example.com/v1',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now()->subDay(),
            'is_current' => false,
        ]);
        ModVersion::query()->create([
            'mod_id' => $mod->id,
            'submitted_by' => $user->id,
            'version' => '1.1.0',
            'normalized_version' => '1.1.0.0',
            'changelog' => 'Feature update.',
            'external_download_url' => 'https://example.com/v1-1',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
            'is_current' => true,
        ]);

        $this->assertSame(30, app(RankService::class)->pointsForUser($user));
        $this->assertSame(1, app(RankService::class)->approvedNewVersionsCount($user));
    }

    public function test_received_ratings_and_high_average_rating_bonus_grant_points(): void
    {
        RankPointRule::query()->where('key', RankPointRule::RATING_AVERAGE_BONUS)->update(['threshold' => 2]);

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'is_active' => true,
        ]);
        $mod = Mod::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Highly Rated Mod',
            'slug' => 'highly-rated-mod',
            'description' => 'An approved mod with strong ratings.',
            'external_download_url' => 'https://example.com/highly-rated-mod',
            'status' => Mod::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        Rating::query()->create(['user_id' => User::factory()->create()->id, 'mod_id' => $mod->id, 'score' => 5]);
        Rating::query()->create(['user_id' => User::factory()->create()->id, 'mod_id' => $mod->id, 'score' => 4]);

        $this->assertSame(174, app(RankService::class)->pointsForUser($user));
    }
}
