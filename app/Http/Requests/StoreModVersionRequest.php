<?php

namespace App\Http\Requests;

use App\Models\ModVersion;
use App\Services\VersionNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(VersionNormalizer $versionNormalizer): array
    {
        $mod = $this->route('mod');
        $normalizedVersion = $versionNormalizer->normalize((string) $this->input('version'));

        return [
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
            'normalized_version' => [
                Rule::unique(ModVersion::class, 'normalized_version')->where('mod_id', $mod?->id),
            ],
            'changelog' => ['required', 'string', 'min:10', 'max:10000'],
            'external_download_url' => ['required', 'url:http,https', 'max:2048'],
            'virus_total_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizedVersion = app(VersionNormalizer::class)->normalize((string) $this->input('version'));

        if ($normalizedVersion !== null) {
            $this->merge(['normalized_version' => $normalizedVersion]);
        }
    }
}
