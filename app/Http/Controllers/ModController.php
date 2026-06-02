<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModRequest;
use App\Jobs\SubmitUrlToVirusTotalJob;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SecurityCheck;
use App\Services\RankService;
use App\Services\VirusTotalService;
use App\Services\WarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ModController extends Controller
{
    public function index(Request $request, RankService $rankService): Response
    {
        $search = trim((string) $request->query('search'));
        $category = trim((string) $request->query('category'));
        $sortBy = trim((string) $request->query('sort_by', 'approved_at'));
        $sortDirection = trim((string) $request->query('sort_direction', 'desc'));

        $allowedSorts = ['approved_at', 'title', 'rating', 'downloads'];
        $allowedDirections = ['asc', 'desc'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'approved_at';
        }

        if (! in_array($sortDirection, $allowedDirections, true)) {
            $sortDirection = 'desc';
        }

        $mods = Mod::query()
            ->with(['category:id,name,slug', 'user:id,name', 'images:id,mod_id,url,file_path,alt_text,sort_order', 'latestSecurityCheck', 'currentVersion.latestSecurityCheck'])
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->where('status', Mod::STATUS_APPROVED)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($query) => $query->where('slug', $category)))
            ->when($sortBy === 'approved_at', fn ($query) => $query->orderBy('approved_at', $sortDirection)->orderBy('id', $sortDirection))
            ->when($sortBy === 'title', fn ($query) => $query->orderBy('title', $sortDirection)->latest('approved_at'))
            ->when($sortBy === 'rating', fn ($query) => $query->orderBy('ratings_avg_score', $sortDirection)->orderBy('ratings_count', $sortDirection)->latest('approved_at'))
            ->when($sortBy === 'downloads', fn ($query) => $query->orderBy('download_clicks_count', $sortDirection)->latest('approved_at'))
            ->paginate(12)
            ->through(fn (Mod $mod): array => $this->modPayload($mod, $rankService))
            ->withQueryString();

        return Inertia::render('Mods/Index', [
            'mods' => $mods,
            'categories' => Category::query()
                ->where('is_active', true)
                ->ordered()
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function show(Mod $mod, Request $request, RankService $rankService): Response
    {
        Gate::authorize('view', $mod);

        $canModerateComments = $request->user()?->hasPermission('moderate_comments');

        $mod->load([
            'category:id,name,slug',
            'user:id,name',
            'images:id,mod_id,url,file_path,alt_text,sort_order',
            'latestSecurityCheck',
            'currentVersion.latestSecurityCheck',
            'approvedVersions.latestSecurityCheck',
            'comments' => fn ($query) => $query
                ->with('user:id,name')
                ->when(! $canModerateComments, fn ($query) => $query->where('status', Comment::STATUS_VISIBLE)),
        ])->loadAvg('ratings', 'score')->loadCount('ratings');

        $userRating = $request->user()
            ? $mod->ratings()->where('user_id', $request->user()->id)->value('score')
            : null;

        return Inertia::render('Mods/Show', [
            'mod' => $this->modPayload($mod, $rankService),
            'comments' => $mod->comments->map(fn (Comment $comment): array => $this->commentPayload($comment))->values()->all(),
            'userRating' => $userRating,
            'canModerateComments' => (bool) $canModerateComments,
        ]);
    }

    public function mine(Request $request, RankService $rankService): Response
    {
        $mods = $request->user()->mods()
            ->with(['category:id,name,slug', 'user:id,name', 'images:id,mod_id,url,file_path,alt_text,sort_order', 'latestSecurityCheck', 'currentVersion.latestSecurityCheck', 'versions.latestSecurityCheck'])
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->latest()
            ->paginate(12)
            ->through(fn (Mod $mod): array => $this->modPayload($mod, $rankService));

        return Inertia::render('Mods/Mine', [
            'mods' => $mods,
        ]);
    }

    public function download(Mod $mod, Request $request): RedirectResponse
    {
        Gate::authorize('view', $mod);

        $mod->loadMissing('currentVersion');

        if ($mod->currentVersion !== null) {
            return redirect()->route('mods.versions.download', [$mod, $mod->currentVersion]);
        }

        $countedDownloads = $request->session()->get('download_clicks_counted', []);

        if (! in_array($mod->id, $countedDownloads, true)) {
            DB::transaction(function () use ($mod): void {
                Mod::query()->whereKey($mod->id)->increment('download_clicks_count');
            });

            $countedDownloads[] = $mod->id;
            $request->session()->put('download_clicks_counted', array_values(array_unique($countedDownloads)));
        }

        abort_if(blank($mod->external_download_url), 404);

        return redirect()->away($mod->external_download_url);
    }

    public function create(WarningService $warningService): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user !== null && $warningService->isUploadBanned($user)) {
            $ban = $warningService->getActiveUploadBan($user);
            $message = $ban !== null
                ? __('messages.sanctions.upload_banned', [
                    'date' => $ban->expires_at?->format('d.m.Y H:i') ?? '—',
                    'reason' => $ban->reason,
                ])
                : __('messages.sanctions.upload_banned', ['date' => '—', 'reason' => '']);

            return redirect()->route('mods.mine')->with('error', $message);
        }

        Gate::authorize('create', Mod::class);

        return Inertia::render('Mods/Create', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->ordered()
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreModRequest $request, VirusTotalService $virusTotalService): RedirectResponse
    {
        Gate::authorize('create', Mod::class);

        $validated = $request->validated();

        $mod = $request->user()->mods()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'external_download_url' => $validated['external_download_url'] ?? null,
            'virus_total_url' => $validated['virus_total_url'] ?? null,
            'slug' => $this->uniqueSlug($request->string('title')->toString()),
            'status' => Mod::STATUS_PENDING,
        ]);

        $version = $mod->versions()->create([
            'submitted_by' => $request->user()->id,
            'version' => $validated['version'],
            'normalized_version' => $validated['normalized_version'],
            'changelog' => $validated['changelog'],
            'external_download_url' => $validated['external_download_url'] ?? null,
            'virus_total_url' => $validated['virus_total_url'] ?? null,
            'youtube_preview_url' => $validated['youtube_preview_url'] ?? null,
            'youtube_video_id' => $validated['youtube_video_id'] ?? null,
            'status' => Mod::STATUS_PENDING,
        ]);

        if ($request->hasFile('audio_file')) {
            $audioFile = $request->file('audio_file');
            $version->update([
                'audio_file_path' => $audioFile->store('mods/audio', 'public'),
                'audio_original_name' => $audioFile->getClientOriginalName(),
                'audio_mime' => $audioFile->getMimeType(),
                'audio_size' => $audioFile->getSize(),
            ]);
        }

        $file = $request->file('image');
        $path = $file->store('mods/screenshots', 'public');

        $mod->images()->create([
            'file_path' => $path,
            'alt_text' => $mod->title.' screenshot',
            'sort_order' => 0,
        ]);

        if ($virusTotalService->isConfigured() && filled($version->external_download_url)) {
            SubmitUrlToVirusTotalJob::dispatch($mod->id, $version->id);
        } else {
            $virusTotalService->recordVersionNotSubmitted($version);
        }

        return redirect()
            ->route('mods.mine')
            ->with('status', 'Mod submitted for review.');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'mod';
        $slug = $base;
        $counter = 2;

        while (Mod::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function modPayload(Mod $mod, RankService $rankService): array
    {
        return [
            'id' => $mod->id,
            'title' => $mod->title,
            'slug' => $mod->slug,
            'description' => $mod->description,
            'external_download_url' => $mod->currentVersion?->external_download_url ?? $mod->external_download_url,
            'virus_total_url' => $mod->currentVersion?->virus_total_url ?? $mod->virus_total_url,
            'download_clicks_count' => $mod->relationLoaded('versions')
                ? $mod->versions->sum('download_clicks_count')
                : ($mod->download_clicks_count ?? 0),
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
            'user' => $mod->user ? [
                'id' => $mod->user->id,
                'name' => $mod->user->name,
                ...$rankService->userRankPayload($mod->user),
            ] : null,
            'current_version' => $mod->currentVersion ? $this->versionPayload($mod->currentVersion) : null,
            'versions' => $mod->relationLoaded('approvedVersions')
                ? $mod->approvedVersions->map(fn (ModVersion $version): array => $this->versionPayload($version))->values()->all()
                : ($mod->relationLoaded('versions') ? $mod->versions->map(fn (ModVersion $version): array => $this->versionPayload($version))->values()->all() : []),
            'security_check' => $mod->currentVersion?->latestSecurityCheck
                ? $this->securityCheckPayload($mod->currentVersion->latestSecurityCheck)
                : ($mod->latestSecurityCheck ? $this->securityCheckPayload($mod->latestSecurityCheck) : null),
            'images' => $mod->images->map(fn ($image): array => [
                'id' => $image->id,
                'mod_id' => $image->mod_id,
                'url' => $image->file_path ? Storage::disk('public')->url($image->file_path) : $image->url,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
            ])->values()->all(),
        ];
    }

    private function securityCheckPayload(SecurityCheck $securityCheck): array
    {
        return [
            'id' => $securityCheck->id,
            'provider' => $securityCheck->provider,
            'status' => $securityCheck->status,
            'external_url' => $securityCheck->external_url,
            'analysis_id' => $securityCheck->analysis_id,
            'result_summary' => $securityCheck->result_summary,
            'checked_at' => $securityCheck->checked_at?->toISOString(),
            'created_at' => $securityCheck->created_at->toISOString(),
            'updated_at' => $securityCheck->updated_at->toISOString(),
        ];
    }

    private function versionPayload(ModVersion $version): array
    {
        return [
            'id' => $version->id,
            'version' => $version->version,
            'normalized_version' => $version->normalized_version,
            'changelog' => $version->changelog,
            'external_download_url' => $version->external_download_url,
            'virus_total_url' => $version->virus_total_url,
            'youtube_preview_url' => $version->youtube_preview_url,
            'youtube_video_id' => $version->youtube_video_id,
            'youtube_embed_url' => $version->youtube_video_id ? 'https://www.youtube-nocookie.com/embed/'.$version->youtube_video_id : null,
            'audio_url' => $version->audio_file_path ? route('mods.versions.audio', [$version->mod, $version]) : null,
            'audio_original_name' => $version->audio_original_name,
            'audio_mime' => $version->audio_mime,
            'audio_size' => $version->audio_size,
            'download_clicks_count' => $version->download_clicks_count ?? 0,
            'status' => $version->status,
            'rejection_reason' => $version->rejection_reason,
            'approved_at' => $version->approved_at?->toISOString(),
            'created_at' => $version->created_at->toISOString(),
            'updated_at' => $version->updated_at->toISOString(),
            'is_current' => (bool) $version->is_current,
            'security_check' => $version->latestSecurityCheck ? $this->securityCheckPayload($version->latestSecurityCheck) : null,
        ];
    }

    private function commentPayload(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'status' => $comment->status,
            'created_at' => $comment->created_at->toISOString(),
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ],
        ];
    }
}
