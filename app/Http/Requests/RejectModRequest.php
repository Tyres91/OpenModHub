<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectModRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mod = $this->route('mod') ?? $this->route('modVersion')?->mod;

        return $this->user()?->can('reject', $mod) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
