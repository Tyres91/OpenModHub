<?php

namespace App\Http\Controllers;

use App\Models\Mod;
use App\Models\User;
use App\Services\RankService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function show(User $user, RankService $rankService): Response
    {
        $mods = $user->mods()
            ->with(['category:id,name,slug', 'user:id,name', 'images:id,mod_id,url,file_path,alt_text,sort_order'])
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->where('status', Mod::STATUS_APPROVED)
            ->latest('approved_at')
            ->paginate(9)
            ->through(fn (Mod $mod): array => $this->modPayload($mod, $rankService))
            ->withQueryString();

        return Inertia::render('Users/Show', [
            'profileUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'created_at' => $user->created_at->toISOString(),
                ...$rankService->userRankPayload($user),
            ],
            'mods' => $mods,
        ]);
    }

    /** @return array<string, mixed> */
    private function modPayload(Mod $mod, RankService $rankService): array
    {
        return [
            'id' => $mod->id,
            'title' => $mod->title,
            'slug' => $mod->slug,
            'description' => $mod->description,
            'external_download_url' => $mod->external_download_url,
            'virus_total_url' => $mod->virus_total_url,
            'status' => $mod->status,
            'rejection_reason' => $mod->rejection_reason,
            'ratings_avg_score' => $mod->ratings_avg_score !== null ? round((float) $mod->ratings_avg_score, 1) : null,
            'ratings_count' => $mod->ratings_count ?? 0,
            'approved_at' => $mod->approved_at?->toISOString(),
            'created_at' => $mod->created_at->toISOString(),
            'updated_at' => $mod->updated_at->toISOString(),
            'category' => $mod->category ? [
                'id' => $mod->category->id,
                'name' => $mod->category->name,
                'slug' => $mod->category->slug,
            ] : null,
            'user' => [
                'id' => $mod->user->id,
                'name' => $mod->user->name,
                ...$rankService->userRankPayload($mod->user),
            ],
            'images' => $mod->images->map(fn ($image): array => [
                'id' => $image->id,
                'mod_id' => $image->mod_id,
                'url' => $image->file_path ? Storage::disk('public')->url($image->file_path) : $image->url,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
            ])->values()->all(),
        ];
    }
}
