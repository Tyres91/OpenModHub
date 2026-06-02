<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateModRequest;
use App\Models\Category;
use App\Models\Mod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ModController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('reviewAny', Mod::class);

        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $categoryId = $request->query('category_id', '');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $allowedSortBy = ['title', 'created_at', 'download_clicks_count', 'approved_at'];
        if (! in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $query = Mod::query()
            ->with(['category:id,name,slug', 'user:id,name', 'currentVersion']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, [Mod::STATUS_PENDING, Mod::STATUS_APPROVED, Mod::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }

        if ($categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }

        $mods = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(25)
            ->through(fn (Mod $mod): array => $this->modPayload($mod))
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Mods/Index', [
            'mods' => $mods,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category_id' => $categoryId,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function update(AdminUpdateModRequest $request, Mod $mod): RedirectResponse
    {
        Gate::authorize('update', $mod);

        $data = $request->safe()->only(['title', 'description', 'category_id', 'external_download_url', 'virus_total_url', 'status']);

        if ($request->has('title') && $request->input('title') !== $mod->title) {
            $data['slug'] = \Illuminate\Support\Str::slug($request->input('title'));
        }

        $mod->update($data);

        return back()->with('status', __('messages.flash.mod_updated'));
    }

    public function destroy(Request $request, Mod $mod): RedirectResponse
    {
        Gate::authorize('forceDelete', $mod);

        $request->validate([
            'confirm_title' => 'required|string',
        ]);

        if ($request->input('confirm_title') !== $mod->title) {
            return back()->withErrors(['confirm_title' => __('validation.mod_title_mismatch')]);
        }

        $mod->loadMissing(['images', 'versions.securityChecks']);

        foreach ($mod->images as $image) {
            if ($image->file_path) {
                Storage::disk('public')->delete($image->file_path);
            }
        }

        foreach ($mod->versions as $version) {
            if ($version->audio_file_path) {
                Storage::disk('public')->delete($version->audio_file_path);
            }
            $version->securityChecks()->delete();
        }

        $mod->comments()->delete();
        $mod->ratings()->delete();
        $mod->reports()->delete();
        $mod->securityChecks()->delete();
        $mod->versions()->delete();
        $mod->images()->delete();
        $mod->forceDelete();

        return back()->with('status', __('messages.flash.mod_permanently_deleted'));
    }

    /** @return array<string, mixed> */
    private function modPayload(Mod $mod): array
    {
        return [
            'id' => $mod->id,
            'title' => $mod->title,
            'slug' => $mod->slug,
            'description' => $mod->description,
            'external_download_url' => $mod->external_download_url,
            'virus_total_url' => $mod->virus_total_url,
            'download_clicks_count' => $mod->download_clicks_count,
            'status' => $mod->status,
            'rejection_reason' => $mod->rejection_reason,
            'approved_at' => $mod->approved_at?->toISOString(),
            'created_at' => $mod->created_at->toISOString(),
            'updated_at' => $mod->updated_at->toISOString(),
            'category' => $mod->category ? [
                'id' => $mod->category->id,
                'name' => $mod->category->name,
                'slug' => $mod->category->slug,
            ] : null,
            'user' => $mod->user ? [
                'id' => $mod->user->id,
                'name' => $mod->user->name,
            ] : null,
            'current_version' => $mod->currentVersion ? [
                'id' => $mod->currentVersion->id,
                'version' => $mod->currentVersion->version,
                'external_download_url' => $mod->currentVersion->external_download_url,
            ] : null,
        ];
    }
}
