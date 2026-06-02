<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Mod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateModRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('mod')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:10000'],
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists(Category::class, 'id'),
            ],
            'external_download_url' => ['nullable', 'url:http,https', 'max:2048'],
            'virus_total_url' => ['nullable', 'url:http,https', 'max:2048'],
            'status' => ['sometimes', Rule::in([Mod::STATUS_PENDING, Mod::STATUS_APPROVED, Mod::STATUS_REJECTED])],
        ];
    }
}
