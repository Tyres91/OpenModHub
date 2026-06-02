<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Rules\ValidModImage;
use App\Services\VersionNormalizer;
use App\Support\YouTube;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Mod::class) ?? false;
    }

    public function rules(VersionNormalizer $versionNormalizer): array
    {
        $normalizedVersion = $versionNormalizer->normalize((string) $this->input('version'));

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:40', 'max:10000'],
            'version' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, callable $fail) use ($normalizedVersion): void {
                    if ($normalizedVersion === null) {
                        $fail(__('validation.regex', ['attribute' => $attribute]));
                    }
                },
            ],
            'normalized_version' => [Rule::unique(ModVersion::class, 'normalized_version')->where('mod_id', null)],
            'changelog' => ['required', 'string', 'min:10', 'max:10000'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where('is_active', true),
            ],
            'external_download_url' => ['nullable', 'required_without:audio_file', 'url:http,https', 'max:2048'],
            'virus_total_url' => ['nullable', 'url:http,https', 'max:2048'],
            'youtube_preview_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (filled($value) && YouTube::videoIdFromUrl((string) $value) === null) {
                        $fail(__('validation.url', ['attribute' => $attribute]));
                    }
                },
            ],
            'youtube_video_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{11}$/'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3', 'mimetypes:audio/mpeg,audio/mp3', 'max:20480'],
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:5120', new ValidModImage],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizedVersion = app(VersionNormalizer::class)->normalize((string) $this->input('version'));

        if ($normalizedVersion !== null) {
            $this->merge(['normalized_version' => $normalizedVersion]);
        }

        $youtubeVideoId = YouTube::videoIdFromUrl($this->input('youtube_preview_url'));

        if ($youtubeVideoId !== null) {
            $this->merge([
                'youtube_preview_url' => YouTube::canonicalUrl($youtubeVideoId),
                'youtube_video_id' => $youtubeVideoId,
            ]);
        }
    }
}
