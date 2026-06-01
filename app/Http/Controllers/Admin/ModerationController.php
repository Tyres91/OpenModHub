<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForceDeleteModRequest;
use App\Http\Requests\RejectModRequest;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SecurityCheck;
use App\Notifications\ModApprovedNotification;
use App\Notifications\ModRejectedNotification;
use App\Notifications\ModVersionApprovedNotification;
use App\Notifications\ModVersionRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('reviewAny', Mod::class);

        $status = $request->query('status', Mod::STATUS_PENDING);
        $allowedStatuses = [Mod::STATUS_PENDING, Mod::STATUS_APPROVED, Mod::STATUS_REJECTED];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = Mod::STATUS_PENDING;
        }

        $mods = Mod::query()
            ->with(['category:id,name,slug', 'user:id,name', 'images:id,mod_id,url,file_path,alt_text,sort_order', 'latestSecurityCheck', 'versions.latestSecurityCheck'])
            ->where('status', $status)
            ->latest()
            ->paginate(12)
            ->through(fn (Mod $mod): array => $this->modPayload($mod))
            ->withQueryString();

        $modVersions = ModVersion::query()
            ->with(['mod:id,title,slug,status', 'submitter:id,name', 'latestSecurityCheck'])
            ->where('status', $status)
            ->whereHas('mod', fn ($query) => $query->where('status', Mod::STATUS_APPROVED))
            ->latest()
            ->paginate(12, ['*'], 'versions_page')
            ->through(fn (ModVersion $version): array => $this->versionPayload($version))
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Index', [
            'mods' => $mods,
            'modVersions' => $modVersions,
            'status' => $status,
        ]);
    }

    public function approve(Mod $mod, Request $request): RedirectResponse
    {
        Gate::authorize('approve', $mod);

        $mod->update([
            'status' => Mod::STATUS_APPROVED,
            'rejection_reason' => null,
            'approved_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $version = $mod->versions()->oldest()->first();

        if ($version !== null) {
            $mod->versions()->update(['is_current' => false]);
            $version->update([
                'status' => Mod::STATUS_APPROVED,
                'rejection_reason' => null,
                'approved_at' => $mod->approved_at,
                'reviewed_by' => $request->user()->id,
                'is_current' => true,
            ]);
        }

        if ($mod->user && ! $mod->user->isBlocked()) {
            $mod->user->notify(new ModApprovedNotification($mod));
        }

        return back()->with('status', __('messages.flash.mod_approved'));
    }

    public function reject(RejectModRequest $request, Mod $mod): RedirectResponse
    {
        $mod->update([
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => $request->validated('rejection_reason'),
            'approved_at' => null,
            'reviewed_by' => $request->user()->id,
        ]);

        $mod->versions()->where('status', Mod::STATUS_PENDING)->update([
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => $request->validated('rejection_reason'),
            'approved_at' => null,
            'reviewed_by' => $request->user()->id,
            'is_current' => false,
        ]);

        if ($mod->user && ! $mod->user->isBlocked()) {
            $mod->user->notify(new ModRejectedNotification($mod, $request->validated('rejection_reason')));
        }

        return back()->with('status', __('messages.flash.mod_rejected'));
    }

    public function approveVersion(ModVersion $modVersion, Request $request): RedirectResponse
    {
        Gate::authorize('approve', $modVersion->mod);

        $modVersion->mod->versions()->update(['is_current' => false]);

        $modVersion->update([
            'status' => Mod::STATUS_APPROVED,
            'rejection_reason' => null,
            'approved_at' => now(),
            'reviewed_by' => $request->user()->id,
            'is_current' => true,
        ]);

        $modVersion->mod->update([
            'external_download_url' => $modVersion->external_download_url,
            'virus_total_url' => $modVersion->virus_total_url,
            'download_clicks_count' => $modVersion->mod->versions()->sum('download_clicks_count'),
        ]);

        $oldSecurityCheck = $modVersion->mod->latestSecurityCheck;
        if ($oldSecurityCheck && blank($oldSecurityCheck->mod_version_id)) {
            $oldSecurityCheck->update([
                'mod_version_id' => $modVersion->id,
            ]);
        }

        if ($modVersion->submitter && ! $modVersion->submitter->isBlocked()) {
            $modVersion->submitter->notify(new ModVersionApprovedNotification($modVersion));
        }

        return back()->with('status', __('messages.flash.mod_version_approved'));
    }

    public function rejectVersion(RejectModRequest $request, ModVersion $modVersion): RedirectResponse
    {
        Gate::authorize('reject', $modVersion->mod);

        $modVersion->update([
            'status' => Mod::STATUS_REJECTED,
            'rejection_reason' => $request->validated('rejection_reason'),
            'approved_at' => null,
            'reviewed_by' => $request->user()->id,
            'is_current' => false,
        ]);

        if ($modVersion->submitter && ! $modVersion->submitter->isBlocked()) {
            $modVersion->submitter->notify(new ModVersionRejectedNotification($modVersion, $request->validated('rejection_reason')));
        }

        return back()->with('status', __('messages.flash.mod_version_rejected'));
    }

    public function forceDestroy(ForceDeleteModRequest $request, Mod $mod): RedirectResponse
    {
        $request->validated();

        DB::transaction(function () use ($mod): void {
            $mod->loadMissing(['images', 'versions.securityChecks']);

            $this->deleteModFiles($mod);

            $mod->comments()->delete();
            $mod->ratings()->delete();
            $mod->reports()->delete();
            $mod->securityChecks()->delete();

            foreach ($mod->versions as $version) {
                $this->deleteVersionFiles($version);
                $version->securityChecks()->delete();
            }

            $mod->versions()->delete();
            $mod->images()->delete();
            $mod->forceDelete();
        });

        return back()->with('status', __('messages.flash.mod_permanently_deleted'));
    }

    private function deleteModFiles(Mod $mod): void
    {
        foreach ($mod->images as $image) {
            if ($image->file_path) {
                Storage::disk('public')->delete($image->file_path);
            }
        }
    }

    private function deleteVersionFiles(ModVersion $version): void
    {
        if ($version->audio_file_path) {
            Storage::disk('public')->delete($version->audio_file_path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function modPayload(Mod $mod): array
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
            'security_check' => $mod->latestSecurityCheck ? $this->securityCheckPayload($mod->latestSecurityCheck) : null,
            'current_version' => $mod->relationLoaded('versions') && $mod->versions->isNotEmpty()
                ? $this->versionPayload($mod->versions->sortBy('id')->first())
                : null,
            'images' => $mod->images->map(fn ($image): array => [
                'id' => $image->id,
                'mod_id' => $image->mod_id,
                'url' => $image->file_path ? Storage::disk('public')->url($image->file_path) : $image->url,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
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

    /** @return array<string, mixed> */
    private function versionPayload(ModVersion $version): array
    {
        return [
            'id' => $version->id,
            'version' => $version->version,
            'changelog' => $version->changelog,
            'external_download_url' => $version->external_download_url,
            'virus_total_url' => $version->virus_total_url,
            'youtube_preview_url' => $version->youtube_preview_url,
            'youtube_video_id' => $version->youtube_video_id,
            'youtube_embed_url' => $version->youtube_video_id ? 'https://www.youtube-nocookie.com/embed/'.$version->youtube_video_id : null,
            'audio_url' => $version->audio_file_path ? Storage::disk('public')->url($version->audio_file_path) : null,
            'audio_original_name' => $version->audio_original_name,
            'audio_mime' => $version->audio_mime,
            'audio_size' => $version->audio_size,
            'download_clicks_count' => $version->download_clicks_count,
            'status' => $version->status,
            'rejection_reason' => $version->rejection_reason,
            'approved_at' => $version->approved_at?->toISOString(),
            'created_at' => $version->created_at->toISOString(),
            'updated_at' => $version->updated_at->toISOString(),
            'is_current' => (bool) $version->is_current,
            'mod' => [
                'id' => $version->mod->id,
                'title' => $version->mod->title,
                'slug' => $version->mod->slug,
            ],
            'user' => $version->submitter ? [
                'id' => $version->submitter->id,
                'name' => $version->submitter->name,
            ] : null,
            'security_check' => $version->latestSecurityCheck ? $this->securityCheckPayload($version->latestSecurityCheck) : null,
        ];
    }
}
