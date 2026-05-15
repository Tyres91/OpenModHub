<?php

namespace App\Http\Requests;

use App\Models\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('rank')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique(Rank::class, 'name')->ignore($this->route('rank'))],
            'required_points' => ['required', 'integer', 'min:0', 'max:100000000'],
            'color' => ['required', 'string', 'max:32', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:80'],
            'is_special' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_special' => $this->boolean('is_special'),
            'required_published_mods' => (int) $this->input('required_points', 0),
        ]);
    }
}
