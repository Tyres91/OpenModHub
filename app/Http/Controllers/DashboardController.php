<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canSeeEditorialMetrics = $user->hasRole('admin') || $user->hasRole('editor');
        $canSeeUserMetrics = $user->hasRole('admin');

        return Inertia::render('Dashboard', [
            'metrics' => $canSeeEditorialMetrics ? $this->metrics($canSeeUserMetrics) : null,
            'canSeeUserMetrics' => $canSeeUserMetrics,
        ]);
    }

    /** @return array<string, int> */
    private function metrics(bool $includeUserMetrics): array
    {
        $metrics = [
            'pending_mods' => Mod::query()->where('status', Mod::STATUS_PENDING)->count(),
            'pending_versions' => ModVersion::query()
                ->where('status', Mod::STATUS_PENDING)
                ->whereHas('mod', fn ($query) => $query->where('status', Mod::STATUS_APPROVED))
                ->count(),
            'pending_reports' => Report::query()->where('status', Report::STATUS_PENDING)->count(),
            'visible_comments' => Comment::query()->where('status', Comment::STATUS_VISIBLE)->count(),
            'approved_mods' => Mod::query()->where('status', Mod::STATUS_APPROVED)->count(),
            'approved_mods_last_7_days' => Mod::query()
                ->where('status', Mod::STATUS_APPROVED)
                ->where('approved_at', '>=', now()->subDays(7))
                ->count(),
        ];

        if ($includeUserMetrics) {
            $metrics['total_users'] = User::query()->count();
            $metrics['new_users_last_7_days'] = User::query()->where('created_at', '>=', now()->subDays(7))->count();
        }

        return $metrics;
    }
}
