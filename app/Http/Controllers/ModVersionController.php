<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModVersionRequest;
use App\Jobs\SubmitUrlToVirusTotalJob;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Services\VirusTotalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Inertia\Inertia;
use Inertia\Response;

class ModVersionController extends Controller
{
    public function create(Mod $mod): Response
    {
        Gate::authorize('submitVersion', $mod);

        return Inertia::render('Mods/CreateVersion', [
            'mod' => [
                'id' => $mod->id,
                'title' => $mod->title,
                'slug' => $mod->slug,
            ],
        ]);
    }

    public function store(StoreModVersionRequest $request, Mod $mod, VirusTotalService $virusTotalService): RedirectResponse
    {
        Gate::authorize('submitVersion', $mod);

        $validated = $request->validated();

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

        if ($virusTotalService->isConfigured() && filled($version->external_download_url)) {
            SubmitUrlToVirusTotalJob::dispatch($mod->id, $version->id);
        } else {
            $virusTotalService->recordVersionNotSubmitted($version);
        }

        return redirect()
            ->route('mods.mine')
            ->with('status', __('messages.flash.mod_version_submitted'));
    }

    public function download(Mod $mod, ModVersion $modVersion, Request $request): RedirectResponse
    {
        Gate::authorize('viewVersion', [$mod, $modVersion]);

        abort_unless($modVersion->mod_id === $mod->id, 404);

        $countedDownloads = $request->session()->get('mod_version_download_clicks_counted', []);

        if (! in_array($modVersion->id, $countedDownloads, true)) {
            DB::transaction(function () use ($modVersion, $mod): void {
                ModVersion::query()->whereKey($modVersion->id)->increment('download_clicks_count');

                Mod::query()->whereKey($mod->id)->update([
                    'download_clicks_count' => DB::raw(
                        '(SELECT COALESCE(SUM(download_clicks_count), 0) FROM mod_versions WHERE mod_id = '.$mod->id.')'
                    ),
                ]);
            });

            $countedDownloads[] = $modVersion->id;
            $request->session()->put('mod_version_download_clicks_counted', array_values(array_unique($countedDownloads)));
        }

        if (filled($modVersion->external_download_url)) {
            return redirect()->away($modVersion->external_download_url);
        }

        abort_if($modVersion->audio_file_path === null, 404);

        return redirect()->route('mods.versions.audio', [$mod, $modVersion]);
    }

    public function audio(Mod $mod, ModVersion $modVersion): BinaryFileResponse
    {
        Gate::authorize('viewVersion', [$mod, $modVersion]);

        abort_unless($modVersion->mod_id === $mod->id, 404);
        abort_if($modVersion->audio_file_path === null, 404);

        $filePath = Storage::disk('public')->path($modVersion->audio_file_path);
        abort_if(! file_exists($filePath), 404);

        $response = new BinaryFileResponse($filePath);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $modVersion->audio_original_name ?? 'audio.mp3'
        );

        $response->headers->set('Content-Type', $modVersion->audio_mime ?? 'audio/mpeg');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->trustXSendfileTypeHeader();

        return $response;
    }
}
