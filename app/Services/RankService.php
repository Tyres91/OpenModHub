<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Rank;
use App\Models\RankPointRule;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RankService
{
    /** @var Collection<int, Rank>|null */
    private ?Collection $ranks = null;

    /** @var Collection<int, RankPointRule>|null */
    private ?Collection $pointRules = null;

    public function publishedModsCount(User $user): int
    {
        return $user->mods()
            ->where('status', Mod::STATUS_APPROVED)
            ->count();
    }

    public function rankForPublishedCount(int $publishedModsCount): ?Rank
    {
        return $this->ranks()
            ->first(fn (Rank $rank): bool => $rank->required_published_mods <= $publishedModsCount);
    }

    public function pointsForUser(User $user): int
    {
        $points = 0;

        $commentRule = $this->pointRule(RankPointRule::COMMENT_CREATED);
        if ($commentRule?->is_enabled) {
            $points += $user->comments()
                ->where('status', Comment::STATUS_VISIBLE)
                ->count() * $commentRule->points;
        }

        $approvedModRule = $this->pointRule(RankPointRule::APPROVED_MOD);
        if ($approvedModRule?->is_enabled) {
            $points += $this->publishedModsCount($user) * $approvedModRule->points;
        }

        $approvedVersionRule = $this->pointRule(RankPointRule::APPROVED_VERSION);
        if ($approvedVersionRule?->is_enabled) {
            $points += $this->approvedNewVersionsCount($user) * $approvedVersionRule->points;
        }

        $downloadRule = $this->pointRule(RankPointRule::DOWNLOAD_THRESHOLD);
        if ($downloadRule?->is_enabled && $downloadRule->threshold !== null) {
            $points += $user->mods()
                ->where('status', Mod::STATUS_APPROVED)
                ->where('download_clicks_count', '>=', $downloadRule->threshold)
                ->count() * $downloadRule->points;
        }

        $ratingReceivedRule = $this->pointRule(RankPointRule::RATING_RECEIVED);
        if ($ratingReceivedRule?->is_enabled) {
            $points += Rating::query()
                ->whereHas('mod', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', Mod::STATUS_APPROVED))
                ->where('user_id', '!=', $user->id)
                ->count() * $ratingReceivedRule->points;
        }

        $ratingAverageBonusRule = $this->pointRule(RankPointRule::RATING_AVERAGE_BONUS);
        if ($ratingAverageBonusRule?->is_enabled && $ratingAverageBonusRule->threshold !== null) {
            $points += DB::table('mods')
                ->join('ratings', 'mods.id', '=', 'ratings.mod_id')
                ->where('mods.user_id', $user->id)
                ->where('mods.status', Mod::STATUS_APPROVED)
                ->select('mods.id')
                ->groupBy('mods.id')
                ->havingRaw('AVG(ratings.score) >= 4.5')
                ->havingRaw('COUNT(ratings.id) >= ?', [$ratingAverageBonusRule->threshold])
                ->count() * $ratingAverageBonusRule->points;
        }

        return $points;
    }

    public function approvedNewVersionsCount(User $user): int
    {
        return ModVersion::query()
            ->where('submitted_by', $user->id)
            ->where('status', Mod::STATUS_APPROVED)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('mod_versions as earlier_versions')
                    ->whereColumn('earlier_versions.mod_id', 'mod_versions.mod_id')
                    ->whereColumn('earlier_versions.id', '<', 'mod_versions.id');
            })
            ->count();
    }

    public function rankForPoints(int $points): ?Rank
    {
        return $this->ranks()
            ->where('is_special', false)
            ->first(fn (Rank $rank): bool => $rank->required_points <= $points);
    }

    /** @return array{published_mods_count: int, rank: array<string, mixed>|null} */
    public function userRankPayload(User $user): array
    {
        $publishedModsCount = $this->publishedModsCount($user);
        $points = $this->pointsForUser($user);
        $specialRank = $user->specialRank;

        return [
            'published_mods_count' => $publishedModsCount,
            'points' => $points,
            'rank' => $this->rankPayload($specialRank?->is_special ? $specialRank : $this->rankForPoints($points)),
            'is_special_rank_locked' => (bool) ($specialRank?->is_special),
        ];
    }

    /** @return array<string, mixed>|null */
    public function rankPayload(?Rank $rank): ?array
    {
        if ($rank === null) {
            return null;
        }

        return [
            'id' => $rank->id,
            'name' => $rank->name,
            'required_published_mods' => $rank->required_published_mods,
            'required_points' => $rank->required_points,
            'color' => $rank->color,
            'icon' => $rank->icon,
            'is_special' => $rank->is_special,
        ];
    }

    /** @return Collection<int, Rank> */
    private function ranks(): Collection
    {
        return $this->ranks ??= Rank::query()
            ->orderByDesc('required_points')
            ->get();
    }

    private function pointRule(string $key): ?RankPointRule
    {
        return $this->pointRules()->firstWhere('key', $key);
    }

    /** @return Collection<int, RankPointRule> */
    private function pointRules(): Collection
    {
        return $this->pointRules ??= RankPointRule::query()->get();
    }
}
