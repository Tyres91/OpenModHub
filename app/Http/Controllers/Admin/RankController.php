<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRankRequest;
use App\Http\Requests\UpdateRankRequest;
use App\Models\Rank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RankController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Rank::class);

        return Inertia::render('Admin/Ranks/Index', [
            'ranks' => Rank::query()
                ->orderBy('required_published_mods')
                ->orderBy('required_points')
                ->get()
                ->map(fn (Rank $rank): array => $this->rankPayload($rank))
                ->values(),
        ]);
    }

    public function store(StoreRankRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['required_published_mods'] = $data['required_points'];

        Rank::query()->create($data);

        return back()->with('status', __('messages.flash.rank_created'));
    }

    public function update(UpdateRankRequest $request, Rank $rank): RedirectResponse
    {
        $data = $request->validated();
        $data['required_published_mods'] = $data['required_points'];

        $rank->update($data);

        return back()->with('status', __('messages.flash.rank_updated'));
    }

    public function destroy(Rank $rank): RedirectResponse
    {
        Gate::authorize('delete', $rank);

        $rank->delete();

        return back()->with('status', __('messages.flash.rank_deleted'));
    }

    /** @return array<string, mixed> */
    private function rankPayload(Rank $rank): array
    {
        return [
            'id' => $rank->id,
            'name' => $rank->name,
            'required_published_mods' => $rank->required_published_mods,
            'required_points' => $rank->required_points,
            'color' => $rank->color,
            'icon' => $rank->icon,
            'is_special' => $rank->is_special,
            'created_at' => $rank->created_at->toISOString(),
            'updated_at' => $rank->updated_at->toISOString(),
        ];
    }
}
